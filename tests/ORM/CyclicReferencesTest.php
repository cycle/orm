<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Entity\Mapper;
use Spiral\ORM\Heap;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\Comment;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class CyclicReferencesTest extends BaseTest
{
    use TableTrait;

    public function setUp()
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

        $this->makeTable('favorites_map', [
            'user_id'    => 'integer',
            'comment_id' => 'integer'
        ]);

        $this->orm = $this->orm->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
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
                            Relation::PIVOT_TABLE       => 'favorites_map',
                            Relation::PIVOT_DATABASE    => 'default',
                            Relation::PIVOT_COLUMNS     => ['user_id', 'comment_id'],
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THOUGHT_INNER_KEY => 'user_id',
                            Relation::THOUGHT_OUTER_KEY => 'comment_id',
                        ],
                    ],
                ]
            ],
            Comment::class => [
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'user'         => [
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
                            Relation::PIVOT_TABLE       => 'favorites_map',
                            Relation::PIVOT_DATABASE    => 'default',
                            Relation::PIVOT_COLUMNS     => ['user_id', 'comment_id'],
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THOUGHT_INNER_KEY => 'comment_id',
                            Relation::THOUGHT_OUTER_KEY => 'user_id',
                        ],
                    ],
                ]
            ]
        ]));
    }

    public function testCreate()
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
        $tr->store($u);
        $tr->run();

        $this->assertNumWrites(4);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
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

    public function testCreateMultipleLinkedTrees()
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

        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->store($u2);
        $tr->run();

        $this->assertNumWrites(6);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->store($u2);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        $selector->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites');

        $u1 = $selector->wherePK($u->id)->fetchOne();

        $this->assertEquals($u->id, $u1->id);
        $this->assertEquals($u->lastComment->id, $u1->lastComment->id);

        $this->assertEquals($u->lastComment->user->id, $u1->lastComment->user->id);
        $this->assertEquals($u->comments[0]->id, $u1->comments[0]->id);
        $this->assertEquals($u->favorites[0]->id, $u1->favorites[0]->id);
        $this->assertEquals($u->favorites[0]->user->id, $u1->favorites[0]->user->id);

        $fav = [
            $u1->favorites[0]->favoredBy[0]->id,
            $u1->favorites[0]->favoredBy[1]->id
        ];

        $this->assertCount(2, $fav);
        $this->assertContains($u->id, $fav);
        $this->assertContains($u2->id, $fav);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        $selector->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites');

        $u1 = $selector->wherePK(2)->fetchOne();

        $this->assertEquals($u1->id, $u2->id);
        $this->assertEquals($u1->favorites[0]->id, $u2->favorites[0]->id);
    }

    public function testCreateMultipleLinkedTreesExchange()
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
        $tr->store($u);
        $tr->store($u2);
        $tr->run();

        $this->assertNumWrites(10);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->store($u2);
        $tr->run();
        $this->assertNumWrites(0);
    }
}