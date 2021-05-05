<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Identity;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class RenamedPKTest extends BaseTest
{
    use TableTrait;

    public function testCreateEmpty(): void
    {
        $this->createTable1();
        $this->orm = $this->withSchema(new Schema($this->getSchemaArray1()));

        $u = new Identity();

        $this->save($u);

        $this->assertIsInt($u->getId());
    }

    public function testCreateWithPredefinedId(): void
    {
        $this->createTable1();
        $this->orm = $this->withSchema(new Schema($this->getSchemaArray1()));

        $u = new Identity();
        $u->setId(2);
        $u->setKey(42);

        $this->save($u);

        $s = new Select($this->orm->withHeap(new Heap()), Identity::class);
        $data = $s->fetchData();

        $this->assertIsInt(current($data)['id']);
        $this->assertSame(2, $u->getId());
        $this->assertSame(42, $u->getKey());
    }

    public function testChangePK(): void
    {
        $this->createTable2();
        $this->orm = $this->withSchema(new Schema($this->getSchemaArray1()));

        $u = new Identity();
        $u->setId(5);
        $u->setKey(42);

        $this->save($u);

        $this->orm = $this->orm->withHeap(new Heap());
        $data = (new Select($this->orm, Identity::class))
            ->fetchAll();
        $this->assertSame(1, count($data));
        $u = $data[0];
        $u->setId(8);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $data = (new Select($this->orm, Identity::class))
            ->fetchAll();

        $this->assertSame(1, count($data));
        $this->assertSame(8, $data[0]->getId());
    }

    private function createTable2(): void
    {
        $this->makeTable(
            'simple_entity',
            [
                'identity_id' => 'bigInteger',
                'identity_key' => 'integer,nullable',
            ]
        );
    }

    private function createTable1(): void
    {
        $this->makeTable(
            'simple_entity',
            [
                'identity_id' => 'primary',
                'identity_key' => 'integer,nullable',
            ]
        );
    }

    private function getSchemaArray1(): array
    {

        return [
            Identity::class => [
                Schema::ROLE        => 'simple_entity',
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'simple_entity',
                Schema::MAPPER      => Mapper::class,
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => [
                    'id' => 'identity_id',
                    'key' => 'identity_key',
                ],
                Schema::TYPECAST    => [
                    'id' => 'int',
                    'key' => 'int',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ];
    }
}
