<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Cyclic;
use Cycle\ORM\Tests\Fixtures\Pivot;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class DeepCyclicTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('cyclic', [
            'id' => 'primary',
            'name' => 'string',
        ]);
        $this->makeTable(
            'cyclic_pivot',
            ['parent_id' => 'integer', 'child_id' => 'integer'],
            pk: ['parent_id', 'child_id'],
        );
        $this->makeFK('cyclic_pivot', 'parent_id', 'cyclic', 'id');
        $this->makeFK('cyclic_pivot', 'child_id', 'cyclic', 'id');

        $this->orm = $this->withSchema(new Schema([
            Cyclic::class => [
                SchemaInterface::ROLE => 'cyclic',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'cyclic',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'name'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [
                    'collection' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => Cyclic::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => Pivot::class,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'child_id',
                            Relation::THROUGH_OUTER_KEY => 'parent_id',
                        ],
                    ],
                ],
            ],
            Pivot::class => [
                SchemaInterface::ROLE => 'pivot',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'cyclic_pivot',
                SchemaInterface::PRIMARY_KEY => ['parent_id', 'child_id'],
                SchemaInterface::COLUMNS => ['parent_id', 'child_id'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }

    public function testCreateDeepCyclic(): void
    {
        $c1 = new Cyclic('C1');
        $c2 = new Cyclic('C2');
        $c3 = new Cyclic('C3');

        $c1->collection[] = $c2;
        $c1->collection[] = $c3;

        $c2->collection[] = $c1;
        $c2->collection[] = $c3;

        $this->logger->display();
        $this->captureWriteQueries();
        $this->save($c1, $c2, $c3);

        $this->assertNumWrites(7);

        $this->orm->getHeap()->clean();
        [$c1, $c2, $c3] = (new Select($this->orm, Cyclic::class))
            ->load('collection')
            ->orderBy('cyclic.name')
            ->fetchAll();

        $this->assertSame($c1->name, 'C1');
        $this->assertSame($c2->name, 'C2');
        $this->assertSame($c3->name, 'C3');

        $this->assertSame($c1->collection, [$c2, $c3]);
        $this->assertSame($c1->collection, [$c1, $c3]);
    }
}
