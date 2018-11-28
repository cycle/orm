<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Entity\Mapper;
use Spiral\ORM\Entity\Repository;
use Spiral\ORM\Schema;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;

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

        $this->orm = $this->orm->withSchema(new Schema([
            User::class => [
                Schema::ALIAS       => 'user',
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

        $result = $r->find()->orderBy('id', 'DESC')->fetchAll();

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals('hello@world.com', $result[1]->email);
        $this->assertEquals(100.0, $result[1]->balance);

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(2, $result[0]->id);
        $this->assertEquals('another@world.com', $result[0]->email);
        $this->assertEquals(200.0, $result[0]->balance);

        // immutable
        $result = $r->find()->fetchAll();

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