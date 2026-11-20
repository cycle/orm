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

class DoubleLinkedTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testCreateDoubleLink(): void
    {
        $c1 = new Cyclic();
        $c2 = new Cyclic();

        $c1->name = 'c1';
        $c2->name = 'c2';

        $c1->cyclic = $c2;
        $c2->cyclic = $c1;
        QueryAssert::writes(3, fn() => $this->save($c1, $c2));

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        [$a, $b] = $selector->orderBy('id')->fetchAll();
        QueryAssert::reads(0, fn() => \Testo\Assert::same($a->cyclic, $b));
        QueryAssert::writes(0, fn() => $this->save($a, $b));
    }

    #[\Testo\Test]
    public function testCreateDoubleLinkWithInverted(): void
    {
        $c1 = new Cyclic();
        $c2 = new Cyclic();

        $c1->name = 'c1';
        $c2->name = 'c2';

        // inverted
        $c1->cyclic = $c2;
        $c2->cyclic = $c1;
        $c1->other = $c2;
        $c2->other = $c1;
        QueryAssert::writes(3, fn() => $this->save($c1, $c2));
        QueryAssert::writes(0, fn() => $this->save($c1, $c2));

        $this->orm = $this->orm->withHeap(new Heap());
        [$a, $b] = (new Select($this->orm, Cyclic::class))->orderBy('id')->fetchAll();
        QueryAssert::reads(0, function () use ($b, $a): void {
            \Testo\Assert::same($b->cyclic, $a);
            \Testo\Assert::same($a->cyclic, $b);
        });
    }

    #[\Testo\Test]
    public function testSelfReference(): void
    {
        $c1 = new Cyclic();

        // inverted
        $c1->name = 'self-reference';
        $c1->cyclic = $c1;
        $c1->other = $c1;
        QueryAssert::writes(2, function () use ($c1): void {
            $tr = new Transaction($this->orm);
            $tr->persist($c1);
            $tr->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        $a = $selector->orderBy('id')->fetchOne();
        QueryAssert::reads(0, function () use ($a): void {
            \Testo\Assert::same($a->cyclic, $a);
            \Testo\Assert::same($a->other, $a);
        });
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('cyclic', [
            'id' => 'primary',
            'name' => 'string',
            'parent_id' => 'integer,nullable',
        ]);

        $this->orm = $this->withSchema(new Schema([
            Cyclic::class => [
                Schema::ROLE => 'cyclic',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'cyclic',
                Schema::PRIMARY_KEY => 'id',
                Schema::FIND_BY_KEYS => ['parent_id'],
                Schema::COLUMNS => ['id', 'parent_id', 'name'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'cyclic' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Cyclic::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                        ],
                    ],
                    'other' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => Cyclic::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
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
}
