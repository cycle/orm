<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Relation;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\BaseTest;
use Cycle\ORM\Tests\Fixtures\Cyclic;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class DoubleLinkedTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

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

    public function testCreateDoubleLink(): void
    {
        $c1 = new Cyclic();
        $c2 = new Cyclic();

        $c1->name = 'c1';
        $c2->name = 'c2';

        $c1->cyclic = $c2;
        $c2->cyclic = $c1;

        $this->captureWriteQueries();
        $this->save($c1, $c2);
        // 2 inserts + 1 update
        $this->assertNumWrites(3);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        [$a, $b] = $selector->orderBy('id')->fetchAll();

        $this->captureReadQueries();
        $this->assertSame($b, $a->cyclic);
        $this->assertNumReads(0);

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(0);
    }

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

        $this->captureWriteQueries();
        $this->save($c1, $c2);
        // 2 inserts, 1 update
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($c1, $c2);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        [$a, $b] = (new Select($this->orm, Cyclic::class))->orderBy('id')->fetchAll();

        $this->captureReadQueries();
        $this->assertSame($a, $b->cyclic);
        $this->assertSame($b, $a->cyclic);
        $this->assertNumReads(0);
    }

    public function testSelfReference(): void
    {
        $c1 = new Cyclic();

        // inverted
        $c1->name = 'self-reference';
        $c1->cyclic = $c1;
        $c1->other = $c1;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($c1);
        $tr->run();
        $this->assertNumWrites(2);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Cyclic::class);
        $a = $selector->orderBy('id')->fetchOne();

        $this->captureReadQueries();
        $this->assertSame($a, $a->cyclic);
        $this->assertSame($a, $a->other);
        $this->assertNumReads(0);
    }
}
