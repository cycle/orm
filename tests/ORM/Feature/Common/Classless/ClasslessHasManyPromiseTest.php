<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Classless;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class ClasslessHasManyPromiseTest extends OrmTestCase
{
    #[Test]
    public function testInitRelation(): void
    {
        $u = $this->orm->make('user');
        Assert::instanceOf($u->comments, ArrayCollection::class);
    }

    #[Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, 'user');
        $selector->load('comments')->orderBy('user.id');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'comments' => [
                    [
                        'id' => 1,
                        'user_id' => 1,
                        'message' => 'msg 1',
                    ],
                    [
                        'id' => 2,
                        'user_id' => 1,
                        'message' => 'msg 2',
                    ],
                    [
                        'id' => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'comments' => [],
            ],
        ]);
    }

    #[Test]
    public function testPromised(): void
    {
        $selector = new Select($this->orm, 'user');
        $u = $selector->wherePK(1)->fetchOne();

        QueryAssert::reads(0, function () use ($u): void {
            Assert::instanceOf($u->comments, Promise::class);
            Assert::false($u->comments->hasValue());
        });

        // Resolve promise
        $resolved = QueryAssert::reads(1, fn() => $u->comments->fetch());

        Assert::true($u->comments->hasValue());
        Assert::count($resolved, 3);
    }

    #[Test]
    public function testHasManyPromiseLoaded(): void
    {
        $selector = new Select($this->orm, 'user');
        $u = $selector->wherePK(1)->fetchOne();

        QueryAssert::reads(0, function () use (&$p, $u): void {
            Assert::instanceOf($p = $u->comments, Promise::class);
        });

        /** @var Promise $p */
        Assert::false($p->hasValue());
        $p->resolve();
        Assert::array($p->getValue());
        Assert::true($p->hasValue());
    }

    #[Test]
    public function testHasManyPromiseRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $u = $selector->wherePK(1)->fetchOne();

        QueryAssert::reads(0, function () use (&$p, $u): void {
            Assert::instanceOf($p = $u->comments, Promise::class);
        });

        /** @var Promise $p */
        Assert::same($p->getRole(), 'comment');
    }

    #[Test]
    public function testHasManyPromiseScope(): void
    {
        $u = (new Select($this->orm, 'user'))
            ->wherePK(1)->fetchOne();

        QueryAssert::reads(0, function () use (&$r, $u): void {
            Assert::instanceOf($r = $u->comments, ReferenceInterface::class);
        });

        /** @var ReferenceInterface $r */
        Assert::equals($r->getScope(), [
            'user_id' => 1,
        ]);
    }

    #[Test]
    public function testPromisedEmpty(): void
    {
        $selector = new Select($this->orm, 'user');
        $u = $selector->wherePK(2)->fetchOne();

        QueryAssert::reads(0, function () use ($u): void {
            Assert::instanceOf($u->comments, Promise::class);
            Assert::false($u->comments->hasValue());
        });

        Assert::count(QueryAssert::reads(1, fn() => $u->comments->fetch()), 0);
    }

    #[Test]
    public function testNoChanges(): void
    {
        $u = (new Select($this->orm, 'user'))->wherePK(1)->fetchOne();

        QueryAssert::count(0, 0, fn() => $this->save($u));
    }

    #[Test]
    public function testNoChangesWithNoChildren(): void
    {
        $selector = new Select($this->orm, 'user');
        $u = $selector->wherePK(2)->fetchOne();

        QueryAssert::count(0, 0, fn() => $this->save($u));
    }

    #[Test]
    public function testRemoveChildren(): void
    {
        $e = (new Select($this->orm, 'user'))->wherePK(1)->fetchOne();

        $e->comments = $e->comments->fetch();
        $e->comments->remove(1);

        $this->save($e);

        $e = (new Select($this->orm->with(heap: new Heap()), 'user'))
            ->wherePK(1)->fetchOne();

        $e->comments = $e->comments->fetch();
        Assert::count($e->comments, 2);

        Assert::same($e->comments[0]->message, 'msg 1');
        Assert::same($e->comments[1]->message, 'msg 3');
    }

    #[Test]
    public function testAddAndRemoveChildren(): void
    {
        $e = (new Select($this->orm, 'user'))->wherePK(1)->fetchOne();

        $e->comments = $e->comments->fetch();
        $e->comments->remove(1);

        $c = $this->orm->make('comment');
        $c->message = 'msg 4';
        $e->comments->add($c);

        $this->save($e);

        $e = (new Select($this->orm->withHeap(new Heap()), 'user'))
            ->wherePK(1)->fetchOne();

        $e->comments = $e->comments->fetch();
        Assert::count($e->comments, 3);

        Assert::same($e->comments[0]->message, 'msg 1');
        Assert::same($e->comments[1]->message, 'msg 3');
        Assert::same($e->comments[2]->message, 'msg 4');
    }

    #[Test]
    public function testSliceAndSaveToAnotherParent(): void
    {
        $selector = new Select($this->orm, 'user');
        [$a, $b] = $selector->orderBy('user.id')->fetchAll();

        $a->comments = $a->comments->fetch();
        $b->comments = $b->comments->fetch();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 0);

        $b->comments = $a->comments->slice(0, 2);
        foreach ($b->comments as $c) {
            $a->comments->removeElement($c);
        }

        $b->comments[0]->message = 'new b';

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 2);

        QueryAssert::writes(2, fn() => $this->save($a, $b));

        // consecutive
        QueryAssert::writes(0, fn() => $this->save($a, $b));

        [$a, $b] = (new Select($this->orm->withHeap(new Heap()), 'user'))
            ->load('comments', [
                'method' => Select\JoinableLoader::INLOAD,
                'as' => 'comment',
            ])
            ->orderBy('user.id')->fetchAll();

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 2);

        Assert::equals($a->comments[0]->id, 3);
        Assert::equals($b->comments[0]->id, 1);
        Assert::equals($b->comments[1]->id, 2);

        Assert::equals($b->comments[0]->message, 'new b');
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'message' => 'string',
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
                [1, 'msg 3'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            'user' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => 'comment',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
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
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByIDScope::class,
            ],
        ]));
    }
}
