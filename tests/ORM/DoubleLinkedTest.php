<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Entity\Mapper;
use Spiral\ORM\Heap;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\Cyclic;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class DoubleLinkedTest extends BaseTest
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

        $this->orm = $this->orm->withSchema(new Schema([
            Cyclic::class => [
                Schema::ALIAS        => 'cyclic',
                Schema::MAPPER       => Mapper::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'cyclic',
                Schema::PRIMARY_KEY  => 'id',
                Schema::CAPTURE_KEYS => ['parent_id'],
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
                    ],
                    'other'  => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => Cyclic::class,
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

    public function testCreateDoubleLink()
    {
        $c1 = new Cyclic();
        $c2 = new Cyclic();

        $c1->name = 'c1';
        $c2->name = 'c2';

        $c1->cyclic = $c2;
        $c2->cyclic = $c1;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($c1);
        $tr->store($c2);
        $tr->run();

        // 2 inserts + 1 update
        $this->assertNumWrites(3);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, Cyclic::class);
        list ($a, $b) = $selector->orderBy('id')->fetchAll();

        $this->captureReadQueries();
        $this->assertSame($b, $a->cyclic->__resolve());
        $this->assertSame($a, $b->cyclic);
        $this->assertNumReads(0);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();

        $this->assertNumWrites(0);
    }

    public function testCreateDoubleLinkWithInverted()
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
        $tr = new Transaction($this->orm);
        $tr->store($c1);
        $tr->store($c2);
        $tr->run();

        // 2 inserts, 2 updates
        $this->assertNumWrites(4);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, Cyclic::class);
        list ($a, $b) = $selector->orderBy('id')->fetchAll();

        $this->captureReadQueries();
        $this->assertSame($a, $b->cyclic);
        $this->assertSame($b, $a->cyclic->__resolve());
        $this->assertNumReads(0);
    }

    public function testSelfReference()
    {
        $c1 = new Cyclic();

        // inverted
        $c1->name = "self-reference";
        $c1->cyclic = $c1;
        $c1->other = $c1;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($c1);
        $tr->run();
        $this->assertNumWrites(2);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, Cyclic::class);
        $a = $selector->orderBy('id')->fetchOne();

        $this->captureReadQueries();
        $this->assertSame($a, $a->cyclic);
        $this->assertSame($a, $a->other);
        $this->assertNumReads(0);
    }

    // last record 66MB for 5000 (but incomplete)
    // current one 70 mb (can i optimize it more)???
    public function testMemUsage()
    {
        $this->orm = $this->orm->withHeap(new Heap());
        $tr = new Transaction($this->orm);

        for ($i = 0; $i < 5000; $i++) {
            $c1 = new Cyclic();

            // inverted
            $c1->name = "self-reference";
            $c1->cyclic = $c1;

            $tr->store($c1);
        }

        $tr->run();
    }
}