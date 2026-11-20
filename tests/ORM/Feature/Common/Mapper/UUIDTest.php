<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Mapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\SortByMsgScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UUIDMapper;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;

abstract class UUIDTest extends BaseTest
{
    use TableTrait;

    private $u1;
    private $u2;
    private $c1;
    private $c2;
    private $c3;

    #[\Testo\Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.email');

        \Testo\Assert::equals($selector->fetchData(), [
            [
                'id' => $this->u2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'comments' => [],
            ],
            [
                'id' => $this->u1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'comments' => [
                    [
                        'id' => $this->c1,
                        'user_id' => $this->u1,
                        'message' => 'msg 1',
                    ],
                    [
                        'id' => $this->c2,
                        'user_id' => $this->u1,
                        'message' => 'msg 2',
                    ],
                    [
                        'id' => $this->c3,
                        'user_id' => $this->u1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testFetchRelationInload(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments', ['method' => Select\JoinableLoader::INLOAD])->orderBy('user.email');

        \Testo\Assert::equals($selector->fetchData(), [
            [
                'id' => $this->u2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'comments' => [],
            ],
            [
                'id' => $this->u1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'comments' => [
                    [
                        'id' => $this->c1,
                        'user_id' => $this->u1,
                        'message' => 'msg 1',
                    ],
                    [
                        'id' => $this->c2,
                        'user_id' => $this->u1,
                        'message' => 'msg 2',
                    ],
                    [
                        'id' => $this->c3,
                        'user_id' => $this->u1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testAccessRelated(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$b, $a] = $selector->load('comments')->orderBy('user.email')->fetchAll();

        \Testo\Assert::instanceOf($a->comments, Collection::class);
        \Testo\Assert::instanceOf($b->comments, Collection::class);

        \Testo\Assert::count($a->comments, 3);
        \Testo\Assert::count($b->comments, 0);

        \Testo\Assert::equals($a->comments[0]->message, 'msg 1');
        \Testo\Assert::equals($a->comments[1]->message, 'msg 2');
        \Testo\Assert::equals($a->comments[2]->message, 'msg 3');
    }

    #[\Testo\Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments')->orderBy('user.email')->fetchAll();

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(0);
    }

    #[\Testo\Test]
    public function testCreateWithRelations(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->comments->add(new Comment());
        $e->comments->add(new Comment());

        $e->comments[0]->message = 'msg A';
        $e->comments[1]->message = 'msg B';

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(3);

        // consecutive test
        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);

        \Testo\Assert::string($e->id);
        \Testo\Assert::equals(strlen($e->id), 36);

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        \Testo\Assert::true($this->orm->getHeap()->has($e->comments[0]));
        \Testo\Assert::same($this->orm->getHeap()->get($e->comments[0])->getStatus(), Node::MANAGED);
        \Testo\Assert::same($this->orm->getHeap()->get($e->comments[0])->getData()['user_id'], $e->id);

        \Testo\Assert::true($this->orm->getHeap()->has($e->comments[1]));
        \Testo\Assert::same($this->orm->getHeap()->get($e->comments[1])->getStatus(), Node::MANAGED);
        \Testo\Assert::same($this->orm->getHeap()->get($e->comments[1])->getData()['user_id'], $e->id);

        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        \Testo\Assert::equals($selector->wherePK($e->id)->fetchData(), [
            [
                'id' => $e->id,
                'email' => 'test@email.com',
                'balance' => 300.0,
                'comments' => [
                    [
                        'id' => $e->comments[0]->id,
                        'user_id' => $e->id,
                        'message' => 'msg A',
                    ],
                    [
                        'id' => $e->comments[1]->id,
                        'user_id' => $e->id,
                        'message' => 'msg B',
                    ],
                ],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testRemoveChildren(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.email')->load('comments');

        /** @var User $e */
        $e = $selector->wherePK($this->u1)->fetchOne();

        $e->comments->remove(1);

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $selector->orderBy('user.id')->load('comments');

        /** @var User $e */
        $e = $selector->wherePK($this->u1)->fetchOne();

        \Testo\Assert::count($e->comments, 2);

        \Testo\Assert::same($e->comments[0]->message, 'msg 1');
        \Testo\Assert::same($e->comments[1]->message, 'msg 3');
    }

    #[\Testo\Test]
    public function testAddAndRemoveChildren(): void
    {
        /** @var User $e */
        $e = (new Select($this->orm, User::class))
            ->load('comments')
            ->wherePK($this->u1)
            ->fetchOne();

        $e->comments->remove(1);

        $c = new Comment();
        $c->message = 'msg 4';
        $e->comments->add($c);

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(2);

        // consecutive test
        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $selector->load('comments');

        /** @var User $e */
        $e = $selector->wherePK($this->u1)->fetchOne();

        \Testo\Assert::count($e->comments, 3);

        \Testo\Assert::same($e->comments[0]->message, 'msg 1');
        \Testo\Assert::same($e->comments[1]->message, 'msg 3');
        \Testo\Assert::same($e->comments[2]->message, 'msg 4');
    }

    #[\Testo\Test]
    public function testSliceAndSaveToAnotherParent(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$b, $a] = $selector->load('comments')->orderBy('user.email')->fetchAll();

        \Testo\Assert::count($a->comments, 3);
        \Testo\Assert::count($b->comments, 0);

        $b->comments = $a->comments->slice(0, 2);
        foreach ($b->comments as $c) {
            $a->comments->removeElement($c);
        }

        $b->comments[0]->message = 'new b';

        \Testo\Assert::count($a->comments, 1);
        \Testo\Assert::count($b->comments, 2);

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$b, $a] = $selector->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
            'as' => 'comment',
        ])->orderBy('user.email')->fetchAll();

        \Testo\Assert::count($a->comments, 1);
        \Testo\Assert::count($b->comments, 2);

        \Testo\Assert::equals($a->comments[0]->id, $this->c3);

        \Testo\Assert::equals($b->comments[0]->id, $this->c2);
        \Testo\Assert::equals($b->comments[1]->id, $this->c1);

        \Testo\Assert::equals($b->comments[1]->message, 'new b');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'string(36),primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('comment', [
            'id' => 'string(36),primary',
            'user_id' => 'string(36)',
            'message' => 'string',
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');

        // seed
        $this->u1 = Uuid::uuid4()->toString();
        $this->u2 = Uuid::uuid4()->toString();
        $this->c1 = Uuid::uuid4()->toString();
        $this->c2 = Uuid::uuid4()->toString();
        $this->c3 = Uuid::uuid4()->toString();

        $this->getDatabase()->table('user')->insertMultiple(
            ['id', 'email', 'balance'],
            [
                [$this->u1, 'hello@world.com', 100],
                [$this->u2, 'another@world.com', 200],
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['id', 'user_id', 'message'],
            [
                [$this->c1, $this->u1, 'msg 1'],
                [$this->c2, $this->u1, 'msg 2'],
                [$this->c3, $this->u1, 'msg 3'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => UUIDMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => UUIDMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByMsgScope::class,
            ],
        ]));
    }
}
