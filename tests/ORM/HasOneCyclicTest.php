<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Cyclic;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class HasOneCyclicTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('cyclic', [
            'id'        => 'primary',
            'name'      => 'string',
            'parent_id' => 'integer,nullable'
        ]);

        $this->getDatabase()->table('cyclic')->insertMultiple(
            ['parent_id', 'name'],
            [
                [null, 'first'],
                [1, 'second'],
                [3, 'self-reference'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            Cyclic::class => [
                Schema::ROLE         => 'cyclic',
                Schema::MAPPER       => Mapper::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'cyclic',
                Schema::PRIMARY_KEY  => 'id',
                Schema::FIND_BY_KEYS => ['parent_id'],
                Schema::COLUMNS      => ['id', 'parent_id', 'name'],
                Schema::SCHEMA       => [],
                Schema::RELATIONS    => [
                    'cyclic' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Cyclic::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                        ],
                    ]
                ]
            ],
        ]));
    }

    public function testFetchCyclic()
    {
        $selector = new Select($this->orm, Cyclic::class);
        $selector->load('cyclic')->orderBy('cyclic.id');

        $this->assertEquals([
            [
                'id'        => '1',
                'parent_id' => null,
                'name'      => 'first',
                'cyclic'    => [
                    'id'        => '2',
                    'parent_id' => '1',
                    'name'      => 'second',
                ],
            ],
            [
                'id'        => '2',
                'parent_id' => '1',
                'name'      => 'second',
                'cyclic'    => null,
            ],
            [
                'id'        => '3',
                'parent_id' => '3',
                'name'      => 'self-reference',
                'cyclic'    => [
                    'id'        => '3',
                    'parent_id' => '3',
                    'name'      => 'self-reference',
                ],
            ],
        ], $selector->fetchData());
    }

    public function testFetchCyclicRelation()
    {
        $selector = new Select($this->orm, Cyclic::class);
        list($a, $b, $c) = $selector->load('cyclic')->orderBy('cyclic.id')->fetchAll();

        $this->assertSame($b, $a->cyclic);
        $this->assertSame(null, $b->cyclic);
        $this->assertSame($c, $c->cyclic);
    }

    public function testUpdateCyclic()
    {
        $selector = new Select($this->orm, Cyclic::class);
        $c = $selector->load('cyclic')->wherePK(3)->fetchOne();
        $this->assertEquals('self-reference', $c->name);

        $c->name = 'updated';

        $tr = new Transaction($this->orm);
        $tr->persist($c);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), Cyclic::class);
        $c = $selector->load('cyclic')->wherePK(3)->fetchOne();

        $this->assertEquals('updated', $c->name);
        $this->assertSame($c, $c->cyclic);
    }

    public function testCyclicWithoutLoad()
    {
        $selector = new Select($this->orm, Cyclic::class);
        $c = $selector->wherePK(3)->fetchOne();
        $this->assertEquals('self-reference', $c->name);
        $this->assertSame($c, $c->cyclic);
    }

    public function testCreateCyclic()
    {
        $c = new Cyclic();
        $c->name = "new";
        $c->cyclic = $c;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($c);
        $tr->run();
        $this->assertNumWrites(2);

        $selector = new Select($this->orm->withHeap(new Heap()), Cyclic::class);
        $c = $selector->load('cyclic')->wherePK(4)->fetchOne();
        $this->assertEquals('new', $c->name);
        $this->assertSame($c, $c->cyclic);
    }
}