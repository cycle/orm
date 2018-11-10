<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Heap;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\UserDefined\TestEntity;
use Spiral\ORM\Tests\Fixtures\UserDefined\TestMapper;
use Spiral\ORM\Tests\Traits\TableTrait;

abstract class UserDefinedTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('test', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->getDatabase()->table('test')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->orm = $this->orm->withSchema(new Schema([
            'test' => [
                Schema::ALIAS       => 'test',
                Schema::MAPPER      => TestMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'test',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFetchData()
    {
        $selector = new Selector($this->orm, 'test');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
            ]
        ], $selector->fetchData());
    }

    public function testFetchAll()
    {
        $selector = new Selector($this->orm, 'test');
        $result = $selector->fetchAll();

        $this->assertInstanceOf(TestEntity::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(TestEntity::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testFetchOne()
    {
        $selector = new Selector($this->orm, 'test');
        $result = $selector->fetchOne();

        $this->assertInstanceOf(TestEntity::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testWhere()
    {
        $selector = new Selector($this->orm, 'test');
        $result = $selector->where('id', 2)->fetchOne();

        $this->assertInstanceOf(TestEntity::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testHeap()
    {
        $heap = new Heap();
        $this->orm = $this->orm->withHeap($heap);

        $selector = new Selector($this->orm, 'test');
        $result = $selector->fetchOne();

        $this->assertEquals(1, $result->id);

        $this->assertTrue($heap->has(TestEntity::class, $result->id));
        $this->assertTrue($heap->hasInstance($result));
        $this->assertSame(Heap::STATE_LOADED, $heap->getState($result));

        $this->assertEquals(
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
            ],
            $heap->getData($result)
        );
    }
}