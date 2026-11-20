<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Identity;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\Driver;

class RenamedPKTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testCreateEmpty(): void
    {
        $this->createTable1();
        $this->orm = $this->withSchema(new Schema($this->getSchemaArray1()));

        $u = new Identity();

        $this->save($u);

        \Testo\Assert::int($u->getId());
    }

    #[\Testo\Test]
    #[ExceptDriver(Driver::SQLServer)]
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

        \Testo\Assert::int(current($data)['id']);
        \Testo\Assert::same($u->getId(), 2);
        \Testo\Assert::same($u->getKey(), 42);
    }

    #[\Testo\Test]
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
        \Testo\Assert::count($data, 1);
        $u = $data[0];
        $u->setId(8);
        QueryAssert::writes(1, fn() => $this->save($u));

        $data = (new Select($this->orm, Identity::class))
            ->fetchAll();

        \Testo\Assert::count($data, 1);
        \Testo\Assert::same($data[0]->getId(), 8);
    }

    private function createTable2(): void
    {
        $this->makeTable(
            'simple_entity',
            [
                'identity_id' => 'bigInteger',
                'identity_key' => 'integer,nullable',
            ],
        );
    }

    private function createTable1(): void
    {
        $this->makeTable(
            'simple_entity',
            [
                'identity_id' => 'primary',
                'identity_key' => 'integer,nullable',
            ],
        );
    }

    private function getSchemaArray1(): array
    {
        return [
            Identity::class => [
                Schema::ROLE => 'simple_entity',
                Schema::DATABASE => 'default',
                Schema::TABLE => 'simple_entity',
                Schema::MAPPER => Mapper::class,
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => [
                    'id' => 'identity_id',
                    'key' => 'identity_key',
                ],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'key' => 'int',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ];
    }
}
