<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation;

use Cycle\Database\ForeignKeyInterface;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Favorite;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class CyclicReferencesTest extends OrmTestCase
{

    #[\Testo\Test]
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
        QueryAssert::writes(4, function () use (&$tr, $u): void {
            $tr = new Transaction($this->orm);
            $tr->persist($u);
            $tr->run();
        });
        // no changes!
        QueryAssert::writes(0, function () use (&$tr, $u): void {
            $tr = new Transaction($this->orm);
            $tr->persist($u);
            $tr->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites');

        $u1 = $selector->wherePK(1)->fetchOne();

        \Testo\Assert::equals($u1->id, $u->id);
        \Testo\Assert::equals($u1->lastComment->id, $u->lastComment->id);

        \Testo\Assert::equals($u1->lastComment->user->id, $u->lastComment->user->id);
        \Testo\Assert::equals($u1->comments[0]->id, $u->comments[0]->id);
        \Testo\Assert::equals($u1->favorites[0]->id, $u->favorites[0]->id);
        \Testo\Assert::equals($u1->favorites[0]->user->id, $u->favorites[0]->user->id);
        \Testo\Assert::equals($u1->favorites[0]->favoredBy[0]->id, $u->id);
    }

    #[\Testo\Test]
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
        QueryAssert::writes(6, fn() => $this->save($u, $u2));
        // no changes!
        QueryAssert::writes(0, fn() => $this->save($u, $u2));

        $this->orm = $this->orm->withHeap(new Heap());

        $u1 = (new Select($this->orm, User::class))->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites')
            ->wherePK($u->id)->fetchOne();

        \Testo\Assert::equals($u1->id, $u->id);
        \Testo\Assert::equals($u1->lastComment->id, $u->lastComment->id);

        \Testo\Assert::equals($u1->lastComment->user->id, $u->lastComment->user->id);
        \Testo\Assert::equals($u1->comments[0]->id, $u->comments[0]->id);
        \Testo\Assert::equals($u1->favorites[0]->id, $u->favorites[0]->id);
        \Testo\Assert::equals($u1->favorites[0]->user->id, $u->favorites[0]->user->id);

        $fav = [
            (string) $u1->favorites[0]->favoredBy[0]->id,
            (string) $u1->favorites[0]->favoredBy[1]->id,
        ];

        \Testo\Assert::contains($fav, (string) $u->id);
        \Testo\Assert::contains($fav, (string) $u2->id);

        $this->orm = $this->orm->withHeap(new Heap());

        $u1 = (new Select($this->orm, User::class))
            ->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites')
            ->wherePK($u2->id)->fetchOne();

        \Testo\Assert::equals($u2->favorites[0]->id, $u1->favorites[0]->id);
    }

    #[\Testo\Test]
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
        QueryAssert::writes(10, function () use (&$tr, $u, $u2): void {
            $tr = new Transaction($this->orm);
            $tr->persist($u);
            $tr->persist($u2);
            $tr->run();
        });
        // no changes!
        QueryAssert::writes(0, function () use (&$tr, $u, $u2): void {
            $tr = new Transaction($this->orm);
            $tr->persist($u);
            $tr->persist($u2);
            $tr->run();
        });
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'comment_id' => 'integer,nullable',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'message' => 'string',
        ], [
            'user_id' => ['table' => 'user', 'column' => 'id'],
        ]);

        $this->makeTable('favorites', [
            'id' => 'primary',
            'user_id' => 'integer',
            'comment_id' => 'integer',
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');
        $this->makeFK(
            'favorites',
            'user_id',
            'user',
            'id',
            ForeignKeyInterface::NO_ACTION,
            ForeignKeyInterface::NO_ACTION,
        );
        $this->makeFK(
            'favorites',
            'comment_id',
            'comment',
            'id',
            ForeignKeyInterface::NO_ACTION,
            ForeignKeyInterface::NO_ACTION,
        );
        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance', 'comment_id'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'lastComment' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE => true,
                        ],
                    ],
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'favorites' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => Favorite::class,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'comment_id',
                        ],
                    ],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'favoredBy' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => Favorite::class,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'comment_id',
                            Relation::THROUGH_OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Favorite::class => [
                Schema::ROLE => 'favorite',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'favorites',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'comment_id'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
