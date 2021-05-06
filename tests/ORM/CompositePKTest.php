<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class CompositePKTest extends BaseTest
{
    use TableTrait;

    public function testSimpleCreate(): void
    {
        $this->createTable1();

        $u = new CompositePK();
        $u->key1 = 1;
        $u->key2 = 1;

        (new Transaction($this->orm))->persist($u)->run();

        $s = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
        $data = $s->fetchData()[0];

        $this->assertSame(1, $data['key1']);
        $this->assertSame(1, $data['key2']);
        $this->assertSame(null, $data['key3']);
    }

    public function testChangePK(): void
    {
        $this->createTable1();

        $u = new CompositePK();
        $u->key1 = 1;
        $u->key2 = 1;
        $u->key3 = 8;

        $this->save($u);

        $this->orm = $this->orm->withHeap(new Heap());
        $e = (new Select($this->orm, CompositePK::class))
            ->fetchOne();
        $e->key1 = 2;
        $e->key2 = 3;
        $e->key3 = 9;

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(1);

        $e = (new Select($this->orm, CompositePK::class))
            ->fetchOne();
        $this->assertSame(2, $e->key1);
        $this->assertSame(3, $e->key2);
    }

    public function testRemoveCreated(): void
    {
        $this->createTable1();
        $u1 = new CompositePK();
        $u1->key1 = 1;
        $u1->key2 = 2;
        $u2 = new CompositePK();
        $u2->key1 = 3;
        $u2->key2 = 4;
        $this->save($u1);

        (new Transaction($this->orm))
            ->delete($u1)
            ->delete($u2)
            ->run();

        $data = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))->fetchData();
        $this->assertSame([], $data);
    }

    public function testCreateWithHalfSame(): void
    {
        $this->createTable1();

        $u1 = new CompositePK();
        $u1->key1 = 1;
        $u1->key2 = 1;
        $u2 = new CompositePK();
        $u2->key1 = 1;
        $u2->key2 = 2;
        $u3 = new CompositePK();
        $u3->key1 = 2;
        $u3->key2 = 1;

        (new Transaction($this->orm))
            ->persist($u1)
            ->persist($u2)
            ->persist($u3)
            ->run();

        $s = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
        $data = $s->fetchData();

        $this->assertSame([1, 1, 2], array_column($data, 'key1'));
        $this->assertSame([1, 2, 1], array_column($data, 'key2'));
    }

    public function testCreateFullDuplicated(): void
    {
        $this->createTable1();

        $u1 = new CompositePK();
        $u1->key1 = 1;
        $u1->key2 = 1;
        $u2 = new CompositePK();
        $u2->key1 = 1;
        $u2->key2 = 1;

        #todo details
        $this->expectException(\Exception:: class);

        (new Transaction($this->orm))->persist($u1)->persist($u2)->run();
    }

    public function testHeapSelectSame(): void
    {
        $this->createTable1();

        $u1 = new CompositePK();
        $u1->key1 = 1;
        $u1->key2 = 1;
        $u2 = new CompositePK();
        $u2->key1 = 1;
        $u2->key2 = 2;
        $u3 = new CompositePK();
        $u3->key1 = 2;
        $u3->key2 = 1;

        (new Transaction($this->orm))
            ->persist($u1)
            ->persist($u2)
            ->persist($u3)
            ->run();

        $data1 = (new Select($this->orm, CompositePK::class))->where(['key1' => 1, 'key2' => 2])->fetchOne();
        $data2 = (new Select($this->orm, CompositePK::class))->wherePK([1, 2])->fetchOne();
        $data3 = (new Select($this->orm, CompositePK::class))->wherePK(['key1' => 1, 'key2' => 2])->fetchOne();

        $this->assertSame($u2, $data1);
        $this->assertSame($data2, $data1);
        $this->assertSame($data2, $data3);
    }

    protected function createTable1(): void
    {
        $this->makeTable(
            'simple_entity',
            [
                'field1' => 'bigInteger,primary',
                'field2' => 'bigInteger,primary',
                'field3' => 'integer,nullable',
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            CompositePK::class => [
                Schema::ROLE        => 'simple_entity',
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'simple_entity',
                Schema::MAPPER      => Mapper::class,
                // Schema::PRIMARY_KEY => 'key1',
                Schema::PRIMARY_KEY => ['key1', 'key2'],
                Schema::COLUMNS     => [
                    'key1' => 'field1',
                    'key2' => 'field2',
                    'key3' => 'field3',
                ],
                Schema::TYPECAST    => [
                    'key1' => 'int',
                    'key2' => 'int',
                    'key3' => 'int',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }
}
