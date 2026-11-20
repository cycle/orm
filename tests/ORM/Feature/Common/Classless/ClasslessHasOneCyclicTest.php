<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Classless;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class ClasslessHasOneCyclicTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testFetchCyclic(): void
    {
        $selector = new Select($this->orm, 'cyclic');
        $selector->load('cyclic')->orderBy('cyclic.id');

        \Testo\Assert::equals($selector->fetchData(), [
            [
                'id' => '1',
                'parent_id' => null,
                'name' => 'first',
                'cyclic' => [
                    'id' => '2',
                    'parent_id' => '1',
                    'name' => 'second',
                ],
            ],
            [
                'id' => '2',
                'parent_id' => '1',
                'name' => 'second',
                'cyclic' => null,
            ],
            [
                'id' => '3',
                'parent_id' => '3',
                'name' => 'self-reference',
                'cyclic' => [
                    'id' => '3',
                    'parent_id' => '3',
                    'name' => 'self-reference',
                ],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testFetchCyclicRelation(): void
    {
        $selector = new Select($this->orm, 'cyclic');
        [$a, $b, $c] = $selector->load('cyclic')->orderBy('cyclic.id')->fetchAll();

        \Testo\Assert::same($a->cyclic, $b);
        \Testo\Assert::same($b->cyclic, null);
        \Testo\Assert::same($c->cyclic, $c);
    }

    #[\Testo\Test]
    public function testUpdateCyclic(): void
    {
        $selector = new Select($this->orm, 'cyclic');
        $c = $selector->load('cyclic')->wherePK(3)->fetchOne();
        \Testo\Assert::equals($c->name, 'self-reference');

        $c->name = 'updated';

        $tr = new Transaction($this->orm);
        $tr->persist($c);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), 'cyclic');
        $c = $selector->load('cyclic')->wherePK(3)->fetchOne();

        \Testo\Assert::equals($c->name, 'updated');
        \Testo\Assert::same($c->cyclic, $c);
    }

    #[\Testo\Test]
    public function testCyclicWithoutLoad(): void
    {
        $c = (new Select($this->orm, 'cyclic'))->wherePK(3)->fetchOne();
        \Testo\Assert::equals($c->name, 'self-reference');
        \Testo\Assert::same($c->cyclic, $c);
    }

    #[\Testo\Test]
    public function testCreateCyclic(): void
    {
        $c = $this->orm->make('cyclic');
        $c->name = 'new';
        $c->cyclic = $c;
        QueryAssert::writes(2, fn() => $this->save($c));
        QueryAssert::writes(0, fn() => $this->save($c));

        $selector = new Select($this->orm->withHeap(new Heap()), 'cyclic');
        $c = $selector->load('cyclic')->wherePK($c->id)->fetchOne();
        \Testo\Assert::equals($c->name, 'new');
        \Testo\Assert::same($c->cyclic, $c);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('cyclic', [
            'id' => 'primary',
            'name' => 'string',
            'parent_id' => 'integer,nullable',
        ]);

        $this->getDatabase()->table('cyclic')->insertMultiple(
            ['parent_id', 'name'],
            [
                [null, 'first'],
                [1, 'second'],
                [3, 'self-reference'],
            ],
        );

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
                ],
            ],
        ]));
    }
}
