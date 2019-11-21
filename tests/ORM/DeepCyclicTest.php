<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Cyclic;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class DeepCyclicTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('cyclic', [
            'id'        => 'primary',
            'name'      => 'string',
            'parent_id' => 'integer,nullable',
            'other_id'  => 'integer,nullable'
        ]);

        $this->orm = $this->withSchema(new Schema([
            Cyclic::class => [
                Schema::ROLE        => 'cyclic',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'cyclic',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'parent_id', 'other_id', 'name'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'cyclic' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Cyclic::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                        ],
                    ],
                    'other'  => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Cyclic::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'other_id',
                        ],
                    ],
                ]
            ],
        ]));
    }

    public function testCreateDeepCyclic(): void
    {
        $c1 = new Cyclic('C1');
        $c2 = new Cyclic('C2');
        $c3 = new Cyclic('C3');
        $c4 = new Cyclic('C4');
        $c5 = new Cyclic('C5');

        // double cycling
        $c1->cyclic = $c5;
        $c5->cyclic = $c1;

        // triple cycling
        $c2->other = $c3;
        $c3->other = $c4;
        $c4->other = $c2;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($c1);
        $tr->persist($c2);
        $tr->persist($c3);
        $tr->persist($c4);
        $tr->persist($c5);
        $tr->run();

        // 5 inserts and 2 loops
        $this->assertNumWrites(7);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        list($c1, $c2, $c3, $c4, $c5) = $selector
            ->load('cyclic')
            ->load('other')
            ->orderBy('cyclic.name')
            ->fetchAll();

        $this->assertSame($c1->name, 'C1');
        $this->assertSame($c2->name, 'C2');
        $this->assertSame($c3->name, 'C3');
        $this->assertSame($c4->name, 'C4');
        $this->assertSame($c5->name, 'C5');

        $this->assertSame($c1->cyclic, $c5);
        $this->assertSame($c5->cyclic, $c1);

        $this->assertSame($c2->other, $c3);
        $this->assertSame($c3->other, $c4);
        $this->assertSame($c4->other, $c2);
    }

    // make sure that graph transformation is homogeneous
    public function testCreateDeepCyclicPartial(): void
    {
        $c1 = new Cyclic('C1');
        $c2 = new Cyclic('C2');
        $c3 = new Cyclic('C3');
        $c4 = new Cyclic('C4');
        $c5 = new Cyclic('C5');

        // double cycling
        $c1->cyclic = $c5;
        $c5->cyclic = $c1;

        // triple cycling
        $c2->other = $c3;
        $c3->other = $c4;
        $c4->other = $c2;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($c1);
        $tr->persist($c2);
        $tr->run();

        // 5 inserts and 2 loops
        $this->assertNumWrites(7);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        list($c1, $c2, $c3, $c4, $c5) = $selector
            ->load('cyclic')
            ->load('other')
            ->orderBy('cyclic.name')
            ->fetchAll();

        $this->assertSame($c1->name, 'C1');
        $this->assertSame($c2->name, 'C2');
        $this->assertSame($c3->name, 'C3');
        $this->assertSame($c4->name, 'C4');
        $this->assertSame($c5->name, 'C5');

        $this->assertSame($c1->cyclic, $c5);
        $this->assertSame($c5->cyclic, $c1);

        $this->assertSame($c2->other, $c3);
        $this->assertSame($c3->other, $c4);
        $this->assertSame($c4->other, $c2);
    }

    // make sure that graph transformation is homogeneous
    public function testCreateDeepCyclicPartial2(): void
    {
        $c1 = new Cyclic('C1');
        $c2 = new Cyclic('C2');
        $c3 = new Cyclic('C3');
        $c4 = new Cyclic('C4');
        $c5 = new Cyclic('C5');

        // double cycling
        $c1->cyclic = $c5;
        $c5->cyclic = $c1;

        // triple cycling
        $c2->other = $c3;
        $c3->other = $c4;
        $c4->other = $c2;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($c5);
        $tr->persist($c4);
        $tr->run();

        // 5 inserts and 2 loops
        $this->assertNumWrites(7);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        list($c1, $c2, $c3, $c4, $c5) = $selector
            ->load('cyclic')
            ->load('other')
            ->orderBy('cyclic.name')
            ->fetchAll();

        $this->assertSame($c1->name, 'C1');
        $this->assertSame($c2->name, 'C2');
        $this->assertSame($c3->name, 'C3');
        $this->assertSame($c4->name, 'C4');
        $this->assertSame($c5->name, 'C5');

        $this->assertSame($c1->cyclic, $c5);
        $this->assertSame($c5->cyclic, $c1);

        $this->assertSame($c2->other, $c3);
        $this->assertSame($c3->other, $c4);
        $this->assertSame($c4->other, $c2);
    }

    // make sure that graph transformation is homogeneous
    public function testOverlappingCycles(): void
    {
        $c1 = new Cyclic('C1');
        $c2 = new Cyclic('C2');
        $c3 = new Cyclic('C3');
        $c4 = new Cyclic('C4');
        $c5 = new Cyclic('C5');

        // double cycling
        $c1->cyclic = $c5;
        $c5->cyclic = $c1;

        // triple cycling
        $c2->other = $c3;
        $c3->other = $c4;
        $c4->other = $c1;
        $c1->other = $c5;
        $c5->other = $c2;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($c1);
        $tr->persist($c2);
        $tr->persist($c3);
        $tr->persist($c4);
        $tr->persist($c5);
        $tr->run();

        // 5 inserts and 2 loops
        $this->assertNumWrites(7);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        list($c1, $c2, $c3, $c4, $c5) = $selector
            ->load('cyclic')
            ->load('other')
            ->orderBy('cyclic.name')
            ->fetchAll();

        $this->assertSame($c1->name, 'C1');
        $this->assertSame($c2->name, 'C2');
        $this->assertSame($c3->name, 'C3');
        $this->assertSame($c4->name, 'C4');
        $this->assertSame($c5->name, 'C5');

        $this->assertSame($c1->cyclic, $c5);
        $this->assertSame($c5->cyclic, $c1);

        $this->assertSame($c2->other, $c3);
        $this->assertSame($c3->other, $c4);
        $this->assertSame($c4->other, $c1);
        $this->assertSame($c1->other, $c5);
        $this->assertSame($c5->other, $c2);
    }
}
