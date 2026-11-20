<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Classless;

use Cycle\Database\ForeignKeyInterface;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class ClasslessCyclicReferencesTest extends OrmTestCase
{
    #[Test]
    public function testCreate(): void
    {
        $u = $this->orm->make('user');
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $c = $this->orm->make('comment');
        $c->user = $u;
        $c->message = 'hello world';

        $u->lastComment = $c;
        $u->comments[] = $c;
        $u->favorites->add($c);

        QueryAssert::writes(4, fn() => $this->save($u));

        // no changes!
        QueryAssert::writes(0, fn() => $this->save($u, $c));

        $this->orm = $this->orm->withHeap(new Heap());
        $u1 = (new Select($this->orm, 'user'))
            ->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites')
            ->wherePK(1)
            ->fetchOne();

        Assert::equals($u1->id, $u->id);
        Assert::equals($u1->lastComment->id, $u->lastComment->id);

        Assert::equals($u1->lastComment->user->id, $u->lastComment->user->id);
        Assert::equals($u1->comments[0]->id, $u->comments[0]->id);
        Assert::equals($u1->favorites[0]->id, $u->favorites[0]->id);
        Assert::equals($u1->favorites[0]->user->id, $u->favorites[0]->user->id);
        Assert::equals($u1->favorites[0]->favoredBy[0]->id, $u->id);
    }

    #[Test]
    public function testCreateMultipleLinkedTrees(): void
    {
        $u = $this->orm->make('user');
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $u2 = $this->orm->make('user');
        $u2->email = 'u2@email.com';
        $u2->balance = 1000;

        $c = $this->orm->make('comment');
        $c->user = $u;
        $c->message = 'hello world';

        $u->lastComment = $c;
        $u->comments[] = $c;
        $u->favorites->add($c);
        $u2->favorites->add($c);

        QueryAssert::writes(6, fn() => $this->save($u, $u2));

        // no changes!
        QueryAssert::writes(0, fn() => $this->save($u, $u2));

        $this->orm = $this->orm->withHeap(new Heap());

        $u1 = (new Select($this->orm, 'user'))
            ->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites')
            ->wherePK($u->id)->fetchOne();

        Assert::equals($u1->id, $u->id);
        Assert::equals($u1->lastComment->id, $u->lastComment->id);

        Assert::equals($u1->lastComment->user->id, $u->lastComment->user->id);
        Assert::equals($u1->comments[0]->id, $u->comments[0]->id);
        Assert::equals($u1->favorites[0]->id, $u->favorites[0]->id);
        Assert::equals($u1->favorites[0]->user->id, $u->favorites[0]->user->id);

        $fav = [
            (string) $u1->favorites[0]->favoredBy[0]->id,
            (string) $u1->favorites[0]->favoredBy[1]->id,
        ];

        Assert::contains($fav, (string) $u->id);
        Assert::contains($fav, (string) $u2->id);

        $this->orm = $this->orm->withHeap(new Heap());

        $u1 = (new Select($this->orm, 'user'))
            ->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favoredBy')
            ->load('favorites')
            ->wherePK($u2->id)->fetchOne();

        Assert::equals($u2->id, (string) $u1->id);
        Assert::equals($u2->favorites[0]->id, (string) $u1->favorites[0]->id);
    }

    #[Test]
    public function testCreateMultipleLinkedTreesExchange(): void
    {
        $u = $this->orm->make('user');
        $u->email = 'u1@email.com';
        $u->balance = 1000;

        $u2 = $this->orm->make('user');
        $u2->email = 'u2@email.com';
        $u2->balance = 1000;

        $c = $this->orm->make('comment');
        $c->user = $u;
        $c->message = 'hello u1';

        $c2 = $this->orm->make('comment');
        $c2->user = $u2;
        $c2->message = 'hello u2';

        $u->lastComment = $c;
        $u->comments[] = $c;

        $u2->lastComment = $c2;
        $u2->comments[] = $c2;

        $u->favorites->add($c2);
        $u2->favorites->add($c);
        $u->favorites->add($c);
        $u2->favorites->add($c2);

        QueryAssert::writes(10, fn() => $this->save($u, $u2));

        // no changes!
        QueryAssert::writes(0, fn() => $this->save($u, $u2));
    }

    #[BeforeTest]
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
            'user' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance', 'comment_id'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'lastComment' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => 'comment',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE => true,
                        ],
                    ],
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => 'comment',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'favorites' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'comment',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'favorite',
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'comment_id',
                        ],
                    ],
                ],
            ],
            'comment' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => 'user',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'favoredBy' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'user',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'favorite',
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'comment_id',
                            Relation::THROUGH_OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            'favorite' => [
                Schema::MAPPER => StdMapper::class,
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
