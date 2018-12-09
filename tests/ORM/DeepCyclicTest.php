<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Mapper\Mapper;
use Spiral\ORM\Heap;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\Cyclic;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class DeepCyclicTest extends BaseTest
{
    use TableTrait;

    public function setUp()
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
                Schema::ALIAS       => 'cyclic',
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

    public function testCreateDeepCyclic()
    {
        $c1 = new Cyclic("C1");
        $c2 = new Cyclic("C2");
        $c3 = new Cyclic("C3");
        $c4 = new Cyclic("C4");
        $c5 = new Cyclic("C5");

        // double cycling
        $c1->cyclic = $c5;
        $c5->cyclic = $c1;

        // triple cycling
        $c2->other = $c3;
        $c3->other = $c4;
        $c4->other = $c2;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($c1);
        $tr->store($c2);
        $tr->store($c3);
        $tr->store($c4);
        $tr->store($c5);
        $tr->run();

        // 5 inserts and 2 loops
        $this->assertNumWrites(7);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, Cyclic::class);
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
    public function testCreateDeepCyclicPartial()
    {
        $c1 = new Cyclic("C1");
        $c2 = new Cyclic("C2");
        $c3 = new Cyclic("C3");
        $c4 = new Cyclic("C4");
        $c5 = new Cyclic("C5");

        // double cycling
        $c1->cyclic = $c5;
        $c5->cyclic = $c1;

        // triple cycling
        $c2->other = $c3;
        $c3->other = $c4;
        $c4->other = $c2;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($c1);
        $tr->store($c2);
        $tr->run();

        // 5 inserts and 2 loops
        $this->assertNumWrites(7);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, Cyclic::class);
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
    public function testCreateDeepCyclicPartial2()
    {
        $c1 = new Cyclic("C1");
        $c2 = new Cyclic("C2");
        $c3 = new Cyclic("C3");
        $c4 = new Cyclic("C4");
        $c5 = new Cyclic("C5");

        // double cycling
        $c1->cyclic = $c5;
        $c5->cyclic = $c1;

        // triple cycling
        $c2->other = $c3;
        $c3->other = $c4;
        $c4->other = $c2;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($c5);
        $tr->store($c4);
        $tr->run();

        // 5 inserts and 2 loops
        $this->assertNumWrites(7);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, Cyclic::class);
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
    public function testOverlappingCycles()
    {
        $c1 = new Cyclic("C1");
        $c2 = new Cyclic("C2");
        $c3 = new Cyclic("C3");
        $c4 = new Cyclic("C4");
        $c5 = new Cyclic("C5");

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
        $tr->store($c1);
        $tr->store($c2);
        $tr->store($c3);
        $tr->store($c4);
        $tr->store($c5);
        $tr->run();

        // 5 inserts and 2 loops
        $this->assertNumWrites(7);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, Cyclic::class);
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