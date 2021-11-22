<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Benchmark;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class BenchmarkClasslessDoubleLinkedTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        if (!BaseTest::$config['benchmark']) {
            $this->markTestSkipped();
            return;
        }

        parent::setUp();

        $this->makeTable('cyclic', [
            'id' => 'primary',
            'name' => 'string',
            'parent_id' => 'integer,nullable',
        ]);

        $this->orm = $this->withSchema(new Schema([
            'cyclic' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'cyclic',
                Schema::PRIMARY_KEY => 'id',
                Schema::FIND_BY_KEYS => ['parent_id'],
                Schema::COLUMNS => ['id', 'parent_id', 'name'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'cyclic' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => 'cyclic',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                        ],
                    ],
                    'other' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => 'cyclic',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'parent_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testMemoryUsage(): void
    {
        $this->orm = $this->withHeap(new Heap());
        $tr = new Transaction($this->orm);

        for ($i = 0; $i < 10000; $i++) {
            // inverted
            $c1 = $this->orm->make('cyclic');
            $c1->name = 'self-reference';
            $c1->cyclic = $c1;

            $tr->persist($c1);
        }

        $tr->run();
    }

    public function testMemoryUsageOther(): void
    {
        $this->orm = $this->withHeap(new Heap());
        $tr = new Transaction($this->orm);

        for ($i = 0; $i < 10000; $i++) {
            // inverted
            $c1 = $this->orm->make('cyclic');
            $c1->name = 'self-reference';
            $c1->other = $c1;

            $tr->persist($c1);
        }

        $tr->run();
    }

    public function testMemoryUsageDouble(): void
    {
        $this->orm = $this->withHeap(new Heap());
        $tr = new Transaction($this->orm);

        for ($i = 0; $i < 10000; $i++) {
            // inverted
            $c1 = $this->orm->make('cyclic');
            $c1->name = 'self-reference';

            $c1->cyclic = $c1;
            $c1->other = $c1;

            $tr->persist($c1);
        }

        $tr->run();
    }
}
