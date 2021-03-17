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

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'simple_entity',
            [
                'identity_id' => 'primary',
                'identity_key' => 'integer',
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
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
                ]
            )
        );
    }

    public function testCreateWithPredefinedId(): void
    {
        $u = new Identity();
        $u->setId(2);
        $u->setKey(42);

        $_POST['entity'] = $u;

        (new Transaction($this->orm))->persist($u)->run();

        $s = new Select($this->orm->withHeap(new Heap()), Identity::class);
        $data = $s->fetchData();

        $this->assertIsInt(current($data)['id']);
        $this->assertSame(2, $u->getId());
        $this->assertSame(42, $u->getKey());
    }
}
