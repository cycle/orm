<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasMany;

use Cycle\ORM\Exception\Relation\BadRelationValueException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class HasManyRelationTest extends OrmTestCase
{
    #[Test]
    public function testInitRelation(): void
    {
        $u = $this->orm->make(User::class);
        Assert::instanceOf($u->comments, ArrayCollection::class);
    }

    #[Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
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
    public function testFetchRelationInload(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments', ['method' => JoinableLoader::INLOAD])->orderBy('user.id');

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
    public function testWithNoColumns(): void
    {
        $selector = new Select($this->orm, User::class);
        $data = $selector->with('comments')->buildQuery()->fetchAll();
        Assert::count($data[0], 3);
    }

    #[Test]
    public function testAccessRelated(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments')->orderBy('user.id')->fetchAll();

        Assert::instanceOf($a->comments, Collection::class);
        Assert::instanceOf($b->comments, Collection::class);

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 0);

        Assert::equals($a->comments[0]->message, 'msg 1');
        Assert::equals($a->comments[1]->message, 'msg 2');
        Assert::equals($a->comments[2]->message, 'msg 3');
    }

    #[Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments')->orderBy('user.id')->fetchAll();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);
    }

    #[Test]
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

        Assert::equals($e->id, 3);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        Assert::true($this->orm->getHeap()->has($e->comments[0]));
        Assert::same($this->orm->getHeap()->get($e->comments[0])->getStatus(), Node::MANAGED);
        Assert::same($this->orm->getHeap()->get($e->comments[0])->getData()['user_id'], $e->id);

        Assert::true($this->orm->getHeap()->has($e->comments[1]));
        Assert::same($this->orm->getHeap()->get($e->comments[1])->getStatus(), Node::MANAGED);
        Assert::same($this->orm->getHeap()->get($e->comments[1])->getData()['user_id'], $e->id);

        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        Assert::equals($selector->wherePK(3)->fetchData(), [
            [
                'id' => 3,
                'email' => 'test@email.com',
                'balance' => 300.0,
                'comments' => [
                    [
                        'id' => 4,
                        'user_id' => 3,
                        'message' => 'msg A',
                    ],
                    [
                        'id' => 5,
                        'user_id' => 3,
                        'message' => 'msg B',
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function testRemoveChildren(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id')->load('comments');

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        $e->comments->remove(1);

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $selector->orderBy('user.id')->load('comments');

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        Assert::count($e->comments, 2);

        Assert::same($e->comments[0]->message, 'msg 1');
        Assert::same($e->comments[1]->message, 'msg 3');
    }

    #[Test]
    public function testRemoveChildrenNullable(): void
    {
        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
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
                            Relation::NULLABLE => true,
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
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
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByIDScope::class,
            ],
        ]));

        /** @var User $e */
        $e = (new Select($this->orm, User::class))
            ->orderBy('user.id')
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        $e->comments->remove(1);

        $this->save($e);

        $this->orm = $this->orm->withHeap(new Heap());
        /** @var User $e */
        $e = (new Select($this->orm, User::class))
            ->orderBy('user.id')->load('comments')->wherePK(1)->fetchOne();

        Assert::count($e->comments, 2);

        Assert::same($e->comments[0]->message, 'msg 1');
        Assert::same($e->comments[1]->message, 'msg 3');

        Assert::same((new Select($this->orm, Comment::class))->count(), 3);
    }

    #[Test]
    public function testAddAndRemoveChildren(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        $e->comments->remove(1);

        $c = new Comment();
        $c->message = 'msg 4';
        $e->comments->add($c);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(2);
        // consecutive test
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $selector->load('comments');

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        Assert::count($e->comments, 3);

        Assert::same($e->comments[0]->message, 'msg 1');
        Assert::same($e->comments[1]->message, 'msg 3');
        Assert::same($e->comments[2]->message, 'msg 4');
    }

    #[Test]
    public function testSliceAndSaveToAnotherParent(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments')->orderBy('user.id')->fetchAll();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 0);

        $b->comments = $a->comments->slice(0, 2);
        foreach ($b->comments as $c) {
            $a->comments->removeElement($c);
        }

        $b->comments[0]->message = 'new b';

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 2);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(2);
        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments', [
            'method' => JoinableLoader::INLOAD,
            'as' => 'comment',
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 2);

        Assert::equals($a->comments[0]->id, 3);
        Assert::equals($b->comments[0]->id, 1);
        Assert::equals($b->comments[1]->id, 2);

        Assert::equals($b->comments[0]->message, 'new b');
    }

    #[Test]
    public function testNotTriggersRehydrate(): void
    {
        $comment = (new Select($this->orm, Comment::class))->wherePK(1)->fetchOne();

        Assert::instanceOf($comment, Comment::class);

        $comment->message = 'updated message';
        Assert::same($comment->message, 'updated message');

        $user = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();

        // Trigger loading of user comments
        $comments = \iterator_to_array($user->comments);
        Assert::contains($comments, $comment);
        Assert::same($comment->message, 'updated message');

        $this->orm = $this->orm->withHeap(new Heap());
    }

    #[Test]
    public function testBadRelationValue(): void
    {
        $e = new User();
        $e->email = 'foo';
        $e->balance = 12.3;
        $e->comments = false;

        Expect::exception(BadRelationValueException::class)->withMessageContaining('must be of the iterable type');

        $this->save($e);
    }

    /**
     * Ignore uninitialized collection
     */
    #[Test]
    public function testCreateWithUninitialisedField(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        unset($e->comments);

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(1);

        Assert::false(isset($e->comments));

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);
    }

    /**
     * Nothing to update if empty collection is replaced with null
     */
    #[Test]
    public function testUpdateEntityWithUnsetCollection(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(1);

        Assert::iterable($e->comments);

        $e->comments = null;

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);
    }

    /**
     * If relation property was unset - ignore this field
     */
    #[Test]
    public function testUnsetProperty(): void
    {
        /** @var User $e */
        $e = (new Select($this->orm, User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        Assert::count($e->comments, 3);
        unset($e->comments);
        // Ignore uninitialized collection
        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);
    }

    /**
     * If collection is replaced with null - remove all children
     */
    #[Test]
    public function testRemoveChildrenUsingSetNull(): void
    {
        /** @var User $e */
        $e = (new Select($this->orm, User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        Assert::count($e->comments, 3);
        $e->comments = null;

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(3);
        // consecutive test
        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);

        $this->orm->getHeap()->clean();

        $e = (new Select($this->orm, User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();
        Assert::count($e->comments, 0);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer,null',
            'message' => 'string',
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
                [1, 'msg 3'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
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
                Schema::MAPPER => Mapper::class,
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
