<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Favorite;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Cycle\Database\ForeignKeyInterface;

abstract class CyclicReferencesTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'         => 'primary',
            'email'      => 'string',
            'balance'    => 'float',
            'comment_id' => 'integer,nullable'
        ]);

        $this->makeTable('comment', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'message' => 'string'
        ], [
            'user_id' => ['table' => 'user', 'column' => 'id']
        ]);

        $this->makeTable('favorites', [
            'id'         => 'primary',
            'user_id'    => 'integer',
            'comment_id' => 'integer'
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');
        $this->makeFK(
            'favorites',
            'user_id',
            'user',
            'id',
            ForeignKeyInterface::NO_ACTION,
            ForeignKeyInterface::NO_ACTION
        );
        $this->makeFK(
            'favorites',
            'comment_id',
            'comment',
            'id',
            ForeignKeyInterface::NO_ACTION,
            ForeignKeyInterface::NO_ACTION
        );
        $this->orm = $this->withSchema(new Schema([
            User::class     => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance', 'comment_id'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'lastComment' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE  => true
                        ],
                    ],
                    'comments'    => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'favorites'   => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE           => true,
                            Relation::THROUGH_ENTITY    => Favorite::class,
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'comment_id',
                        ],
                    ],
                ]
            ],
            Comment::class  => [
                Schema::ROLE        => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'user'      => [
                        Relation::TYPE   => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'favoredBy' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE           => true,
                            Relation::THROUGH_ENTITY    => Favorite::class,
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THROUGH_INNER_KEY => 'comment_id',
                            Relation::THROUGH_OUTER_KEY => 'user_id',
                        ],
                    ],
                ]
            ],
            Favorite::class => [
                Schema::ROLE        => 'favorite',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'favorites',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'comment_id'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testCreate(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $c = new Comment();
        $c->user = $u;
        $c->message = 'hello world';

        $u->addComment($c);
        $u->favorites->add($c);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->assertNumWrites(4);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->load('lastComment.user')
                 ->load('comments.user')
                 ->load('comments.favoredBy')
                 ->load('favorites');

        $u1 = $selector->wherePK(1)->fetchOne();

        $this->assertEquals($u->id, $u1->id);
        $this->assertEquals($u->lastComment->id, $u1->lastComment->id);

        $this->assertEquals($u->lastComment->user->id, $u1->lastComment->user->id);
        $this->assertEquals($u->comments[0]->id, $u1->comments[0]->id);
        $this->assertEquals($u->favorites[0]->id, $u1->favorites[0]->id);
        $this->assertEquals($u->favorites[0]->user->id, $u1->favorites[0]->user->id);
        $this->assertEquals($u->id, $u1->favorites[0]->favoredBy[0]->id);
    }

    public function testCreateMultipleLinkedTrees(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $u2 = new User();
        $u2->email = 'u2@email.com';
        $u2->balance = 1000;

        $c = new Comment();
        $c->user = $u;
        $c->message = 'hello world';

        $u->addComment($c);
        $u->favorites->add($c);
        $u2->favorites->add($c);

        $this->captureWriteQueries();
        $this->save($u, $u2);
        $this->assertNumWrites(6);

        // no changes!
        $this->captureWriteQueries();
        $this->save($u, $u2);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $u1 = (new Select($this->orm, User::class))->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites')
            ->wherePK($u->id)->fetchOne();

        $this->assertEquals($u->id, $u1->id);
        $this->assertEquals($u->lastComment->id, $u1->lastComment->id);

        $this->assertEquals($u->lastComment->user->id, $u1->lastComment->user->id);
        $this->assertEquals($u->comments[0]->id, $u1->comments[0]->id);
        $this->assertEquals($u->favorites[0]->id, $u1->favorites[0]->id);
        $this->assertEquals($u->favorites[0]->user->id, $u1->favorites[0]->user->id);

        $fav = [
            (string)$u1->favorites[0]->favoredBy[0]->id,
            (string)$u1->favorites[0]->favoredBy[1]->id
        ];

        $this->assertContains((string)$u->id, $fav);
        $this->assertContains((string)$u2->id, $fav);

        $this->orm = $this->orm->withHeap(new Heap());

        $u1 = (new Select($this->orm, User::class))
            ->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites')
            ->wherePK($u2->id)->fetchOne();

        $this->assertEquals($u1->favorites[0]->id, $u2->favorites[0]->id);
    }

    public function testCreateMultipleLinkedTreesExchange(): void
    {
        $u = new User();
        $u->email = 'u1@email.com';
        $u->balance = 1000;

        $u2 = new User();
        $u2->email = 'u2@email.com';
        $u2->balance = 1000;

        $c = new Comment();
        $c->user = $u;
        $c->message = 'hello u1';

        $c2 = new Comment();
        $c2->user = $u2;
        $c2->message = 'hello u2';

        $u->addComment($c);
        $u2->addComment($c2);

        $u->favorites->add($c2);
        $u2->favorites->add($c);
        $u->favorites->add($c);
        $u2->favorites->add($c2);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($u2);
        $tr->run();

        $this->assertNumWrites(10);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($u2);
        $tr->run();
        $this->assertNumWrites(0);
    }
}
