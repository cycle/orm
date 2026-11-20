<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Mapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Admin as User;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class MapperTest extends OrmTestCase
{
    #[Test]
    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'protected' => 12,
                'private' => 13,
                'protectedRelation' => [
                    ['user' => 1, 'id' => 1],
                    ['user' => 1, 'id' => 2],
                    ['user' => 1, 'id' => 3],
                ],
                'privateRelation' => [
                    ['user' => 1, 'id' => 1],
                    ['user' => 1, 'id' => 2],
                    ['user' => 1, 'id' => 3],
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'protected' => 14,
                'private' => 15,
                'protectedRelation' => [],
                'privateRelation' => [],
            ],
        ]);
    }

    #[Test]
    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[Test]
    public function testMakeByRole(): void
    {
        Assert::instanceOf($this->orm->make('user'), User::class);
    }

    #[Test]
    public function testMakeByClass(): void
    {
        Assert::instanceOf($this->orm->make(User::class), User::class);
    }

    #[Test]
    public function testAssertRoleViaClass(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
        Assert::equals($result[0]->email, 'hello@world.com');
        Assert::equals($result[0]->balance, 100.0);

        Assert::instanceOf($result[1], User::class);
        Assert::equals($result[1]->id, 2);
        Assert::equals($result[1]->email, 'another@world.com');
        Assert::equals($result[1]->balance, 200.0);
    }

    #[Test]
    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);
        Assert::equals($result->getProtected(), 12);
        Assert::equals($result->getPrivate(), 13);

        $data = $this->orm->getMapper($result)->fetchFields($result);
        Assert::equals([
            'id' => 1,
            'email' => 'hello@world.com',
            'balance' => 100.0,
            'protected' => 12,
            'private' => 13,
        ], $data);
    }

    #[Test]
    public function testFetchOneWithRelation(): void
    {
        $result = (new Select($this->orm, User::class))
            ->load('privateRelation')
            ->load('protectedRelation')
            ->fetchOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);
        Assert::equals($result->getProtected(), 12);
        Assert::equals($result->getPrivate(), 13);
        Assert::count($result->getPrivateRelation(), 3);
        Assert::count($result->getProtectedRelation(), 3);

        $data = $this->orm->getMapper($result)->fetchFields($result);
        Assert::equals([
            'id' => 1,
            'email' => 'hello@world.com',
            'balance' => 100.0,
            'protected' => 12,
            'private' => 13,
        ], $data);
    }

    #[Test]
    public function testFetchSame(): void
    {
        $u = new User();
        $u->email = 'test';
        $u->balance = 100;
        $u->setProtectedRelation([]);
        $u->setPrivateRelation([]);

        $this->save($u);

        $result = (new Select($this->orm, User::class))
            ->orderBy('id', 'DESC')
            ->fetchOne();

        Assert::instanceOf($result, User::class);

        Assert::same($result, $u);
    }

    #[Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        QueryAssert::writes(0, function () use ($result): void {
            $tr = new Transaction($this->orm);
            $tr->persist($result);
            $tr->run();
        });
    }

    #[Test]
    public function testWhere(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 2);
        Assert::equals($result->email, 'another@world.com');
        Assert::equals($result->balance, 200.0);
    }

    #[Test]
    public function testDelete(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $tr = new Transaction($this->orm);
        $tr->delete($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        Assert::null($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, User::class);
        Assert::null($selector->where('id', 2)->fetchOne());

        Assert::false($this->orm->getHeap()->has($result));
    }

    #[Test]
    public function testHeap(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        Assert::equals($result->id, 1);

        Assert::true($this->orm->getHeap()->has($result));
        Assert::same($this->orm->getHeap()->get($result)->getStatus(), Node::MANAGED);

        Assert::equals($this->orm->getHeap()->get($result)->getData(), [
            'id' => 1,
            'email' => 'hello@world.com',
            'balance' => 100.0,
            'protected' => 12,
            'private' => 13,
        ]);
    }

    #[Test]
    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->setProtectedRelation([]);
        $e->setPrivateRelation([]);

        QueryAssert::writes(1, fn() => $this->save($e));

        QueryAssert::writes(0, fn() => $this->save($e));

        Assert::equals($e->id, 3);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);
    }

    #[Test]
    public function testStoreWithUpdate(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->setProtectedRelation([]);
        $e->setPrivateRelation([]);

        QueryAssert::writes(1, function () use ($e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $e->balance = 400;
            $tr->run();
        });

        Assert::equals($e->id, 3);
        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        Assert::equals($result->balance, 400);
    }

    #[Test]
    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
        Assert::equals($result[0]->email, 'hello@world.com');
        Assert::equals($result[0]->balance, 100.0);

        Assert::instanceOf($result[1], User::class);
        Assert::equals($result[1]->id, 2);
        Assert::equals($result[1]->email, 'another@world.com');
        Assert::equals($result[1]->balance, 200.0);
    }

    #[Test]
    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);
    }

    #[Test]
    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id' => 2]);

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 2);
        Assert::equals($result->email, 'another@world.com');
        Assert::equals($result->balance, 200.0);
    }

    #[Test]
    public function testLoadOverwriteValues(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $u->email = 'test@email.com';
        Assert::same($u->email, 'test@email.com');

        $u2 = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::same($u2->email, 'test@email.com');

        $u3 = $this->orm->withHeap(new Heap())->getRepository(User::class)->findByPK(1);
        Assert::same($u3->email, 'hello@world.com');

        QueryAssert::writes(1, fn() => $this->save($u));

        $u4 = $this->orm->withHeap(new Heap())->getRepository(User::class)->findByPK(1);
        Assert::same($u4->email, 'test@email.com');
    }

    #[Test]
    public function testNullableValuesInASndOut(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::equals((float) $u->balance, 100.0);
        $u->balance = 0.0;

        QueryAssert::writes(1, function () use (&$t, $u): void {
            $t = new Transaction($this->orm);
            $t->persist($u);
            $t->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::equals((float) $u->balance, 0.0);

        $u->balance = null;

        QueryAssert::writes(1, function () use (&$t, $u): void {
            $t = new Transaction($this->orm);
            $t->persist($u);
            $t->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::null($u->balance);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable(
            'user',
            [
                'id' => 'primary',
                'email' => 'string',
                'balance' => 'float,nullable',
                'protected' => 'int,nullable',
                'private' => 'int,nullable',
            ],
        );

        $this->makeTable(
            'post',
            [
                'id' => 'primary',
                'user_id' => 'int',
            ],
        );

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance', 'protected', 'private'],
            [
                ['hello@world.com', 100, 12, 13],
                ['another@world.com', 200, 14, 15],
            ],
        );
        $this->getDatabase()->table('post')->insertMultiple(
            ['user_id'],
            [
                [1],
                [1],
                [1],
            ],
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    User::class => [
                        SchemaInterface::ROLE => 'user',
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'user',
                        SchemaInterface::PRIMARY_KEY => 'id',
                        SchemaInterface::COLUMNS => ['id', 'email', 'balance', 'protected', 'private'],
                        SchemaInterface::TYPECAST => ['balance' => 'float'],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [
                            'protectedRelation' => [
                                Relation::TYPE => Relation::HAS_MANY,
                                Relation::TARGET => 'post',
                                Relation::LOAD => Relation::LOAD_EAGER,
                                Relation::SCHEMA => [
                                    Relation::CASCADE => true,
                                    Relation::NULLABLE => false,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'user',
                                ],
                            ],
                            'privateRelation' => [
                                Relation::TYPE => Relation::HAS_MANY,
                                Relation::TARGET => 'post',
                                Relation::LOAD => Relation::LOAD_EAGER,
                                Relation::SCHEMA => [
                                    Relation::CASCADE => true,
                                    Relation::NULLABLE => false,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'user',
                                ],
                            ],
                        ],
                    ],
                    Post::class => [
                        SchemaInterface::ROLE => 'post',
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'post',
                        SchemaInterface::PRIMARY_KEY => 'id',
                        SchemaInterface::COLUMNS => ['id', 'user' => 'user_id'],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [],
                    ],
                ],
            ),
        );
    }
}
