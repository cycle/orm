<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class ColumnAliasesTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id_int' => 'primary',
            'email_str' => 'string',
            'balance_float' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email_str', 'balance_float'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id_int', 'email' => 'email_str', 'balance' => 'balance_float'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(1);

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());
    }

    public function testFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testCloned(): void
    {
        $r = $this->orm->getRepository(User::class);

        /** @var Repository $r2 */
        $r2 = clone $r;

        $this->assertNotSame($r->select(), $r2->select());
    }

    public function testFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testFindDirectOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id_int' => 2]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testFindDirectNull(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id_int' => 3]);

        $this->assertNull($result);
    }

    public function testFindDirectByPK(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findByPK(2);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testFindDirectImmutable(): void
    {
        /** @var Repository $r */
        $r = $this->orm->getRepository(User::class);

        $result = $r->select()->orderBy('id_int', 'DESC')->fetchAll();

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals('hello@world.com', $result[1]->email);
        $this->assertEquals(100.0, $result[1]->balance);

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(2, $result[0]->id);
        $this->assertEquals('another@world.com', $result[0]->email);
        $this->assertEquals(200.0, $result[0]->balance);

        // immutable
        $result = $r->select()->fetchAll();

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }
}
