<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Benchmark;

use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Mapper\StdMapper;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Tests\BaseTest;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

if (!BaseTest::$config['benchmark']) {
    return;
}

abstract class BenchmarkClasslessDoubleLinkedTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        if (!BaseTest::$config['benchmark']) {
            $this->markTestSkipped();
            return;
        }

        parent::setUp();

        $this->makeTable('cyclic', [
            'id'        => 'primary',
            'name'      => 'string',
            'parent_id' => 'integer,nullable'
        ]);

        $this->orm = $this->withSchema(new Schema([
            'cyclic' => [
                Schema::MAPPER       => StdMapper::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'cyclic',
                Schema::PRIMARY_KEY  => 'id',
                Schema::FIND_BY_KEYS => ['parent_id'],
                Schema::COLUMNS      => ['id', 'parent_id', 'name'],
                Schema::SCHEMA       => [],
                Schema::RELATIONS    => [
                    'cyclic' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => 'cyclic',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                        ],
                    ],
                    'other'  => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => 'cyclic',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'parent_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ]
            ],
        ]));
    }

    public function testMemoryUsage()
    {
        $this->orm = $this->orm->withHeap(new Heap());
        $tr = new Transaction($this->orm);

        for ($i = 0; $i < 10000; $i++) {
            // inverted
            $c1 = $this->orm->make('cyclic');
            $c1->name = "self-reference";
            $c1->cyclic = $c1;

            $tr->persist($c1);
        }

        $tr->run();
    }

    public function testMemoryUsageOther()
    {
        $this->orm = $this->orm->withHeap(new Heap());
        $tr = new Transaction($this->orm);

        for ($i = 0; $i < 10000; $i++) {
            // inverted
            $c1 = $this->orm->make('cyclic');
            $c1->name = "self-reference";
            $c1->other = $c1;

            $tr->persist($c1);
        }

        $tr->run();
    }

    public function testMemoryUsageDouble()
    {
        $this->orm = $this->orm->withHeap(new Heap());
        $tr = new Transaction($this->orm);

        for ($i = 0; $i < 10000; $i++) {
            // inverted
            $c1 = $this->orm->make('cyclic');
            $c1->name = "self-reference";

            $c1->cyclic = $c1;
            $c1->other = $c1;

            $tr->persist($c1);
        }

        $tr->run();
    }
}