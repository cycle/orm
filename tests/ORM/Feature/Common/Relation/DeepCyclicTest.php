<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Cyclic;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class DeepCyclicTest extends OrmTestCase
{

    #[\Testo\Test]
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
        QueryAssert::writes(7, fn() => $this->save($c1, $c2, $c3, $c4, $c5));

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        [$c1, $c2, $c3, $c4, $c5] = $selector
            ->load('cyclic')
            ->load('other')
            ->orderBy('cyclic.name')
            ->fetchAll();

        \Testo\Assert::same('C1', $c1->name);
        \Testo\Assert::same('C2', $c2->name);
        \Testo\Assert::same('C3', $c3->name);
        \Testo\Assert::same('C4', $c4->name);
        \Testo\Assert::same('C5', $c5->name);

        \Testo\Assert::same($c5, $c1->cyclic);
        \Testo\Assert::same($c1, $c5->cyclic);

        \Testo\Assert::same($c3, $c2->other);
        \Testo\Assert::same($c4, $c3->other);
        \Testo\Assert::same($c2, $c4->other);
    }

    // make sure that graph transformation is homogeneous
    #[\Testo\Test]
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
        QueryAssert::writes(7, function () use ($c1, $c2): void {
            $tr = new Transaction($this->orm);
            $tr->persist($c1);
            $tr->persist($c2);
            $tr->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        [$c1, $c2, $c3, $c4, $c5] = $selector
            ->load('cyclic')
            ->load('other')
            ->orderBy('cyclic.name')
            ->fetchAll();

        \Testo\Assert::same('C1', $c1->name);
        \Testo\Assert::same('C2', $c2->name);
        \Testo\Assert::same('C3', $c3->name);
        \Testo\Assert::same('C4', $c4->name);
        \Testo\Assert::same('C5', $c5->name);

        \Testo\Assert::same($c5, $c1->cyclic);
        \Testo\Assert::same($c1, $c5->cyclic);

        \Testo\Assert::same($c3, $c2->other);
        \Testo\Assert::same($c4, $c3->other);
        \Testo\Assert::same($c2, $c4->other);
    }

    // make sure that graph transformation is homogeneous
    #[\Testo\Test]
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
        QueryAssert::writes(7, function () use ($c5, $c4): void {
            $tr = new Transaction($this->orm);
            $tr->persist($c5);
            $tr->persist($c4);
            $tr->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        [$c1, $c2, $c3, $c4, $c5] = $selector
            ->load('cyclic')
            ->load('other')
            ->orderBy('cyclic.name')
            ->fetchAll();

        \Testo\Assert::same('C1', $c1->name);
        \Testo\Assert::same('C2', $c2->name);
        \Testo\Assert::same('C3', $c3->name);
        \Testo\Assert::same('C4', $c4->name);
        \Testo\Assert::same('C5', $c5->name);

        \Testo\Assert::same($c5, $c1->cyclic);
        \Testo\Assert::same($c1, $c5->cyclic);

        \Testo\Assert::same($c3, $c2->other);
        \Testo\Assert::same($c4, $c3->other);
        \Testo\Assert::same($c2, $c4->other);
    }

    // make sure that graph transformation is homogeneous
    #[\Testo\Test]
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
        QueryAssert::writes(7, function () use ($c1, $c2, $c3, $c4, $c5): void {
            $tr = new Transaction($this->orm);
            $tr->persist($c1);
            $tr->persist($c2);
            $tr->persist($c3);
            $tr->persist($c4);
            $tr->persist($c5);
            $tr->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        [$c1, $c2, $c3, $c4, $c5] = $selector
            ->load('cyclic')
            ->load('other')
            ->orderBy('cyclic.name')
            ->fetchAll();

        \Testo\Assert::same('C1', $c1->name);
        \Testo\Assert::same('C2', $c2->name);
        \Testo\Assert::same('C3', $c3->name);
        \Testo\Assert::same('C4', $c4->name);
        \Testo\Assert::same('C5', $c5->name);

        \Testo\Assert::same($c5, $c1->cyclic);
        \Testo\Assert::same($c1, $c5->cyclic);

        \Testo\Assert::same($c3, $c2->other);
        \Testo\Assert::same($c4, $c3->other);
        \Testo\Assert::same($c1, $c4->other);
        \Testo\Assert::same($c5, $c1->other);
        \Testo\Assert::same($c2, $c5->other);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('cyclic', [
            'id' => 'primary',
            'name' => 'string',
            'parent_id' => 'integer,nullable',
            'other_id' => 'integer,nullable',
        ]);

        $this->orm = $this->withSchema(new Schema([
            Cyclic::class => [
                Schema::ROLE => 'cyclic',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'cyclic',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'parent_id', 'other_id', 'name'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'cyclic' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Cyclic::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                        ],
                    ],
                    'other' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Cyclic::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'other_id',
                        ],
                    ],
                ],
            ],
        ]));
    }
}
