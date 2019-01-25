<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Mapper\Repository;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;

abstract class RepositoryTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFindAll()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();
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

    public function testCloned()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();

        /** @var Repository $r2 */
        $r2 = clone $r;

        $this->assertNotSame($r->select(), $r2->select());
    }

    public function testFindOne()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();
        $result = $r->findOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testFindOneWithWhere()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();
        $result = $r->findOne(['id' => 2]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testFindNull()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();
        $result = $r->findOne(['id' => 3]);

        $this->assertNull($result);
    }

    public function testFindByPK()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();
        $result = $r->findByPK(2);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testFindImmutable()
    {
        /** @var Repository $r */
        $r = $this->orm->getMapper(User::class)->getRepository();

        $result = $r->select()->orderBy('id', 'DESC')->fetchAll();

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