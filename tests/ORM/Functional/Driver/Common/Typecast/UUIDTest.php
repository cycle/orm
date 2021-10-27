<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\UserWithUUIDPrimaryKey;
use Cycle\ORM\Tests\Fixtures\UuidPrimaryKey;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Ramsey\Uuid\Uuid;

abstract class UUIDTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'user_with_uuid_primary_key',
            [
                'uuid' => 'string(36),primary',
                'email' => 'string',
                'balance' => 'float',
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    UserWithUUIDPrimaryKey::class => [
                        SchemaInterface::ROLE => 'user_with_uuid_primary_key',
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'user_with_uuid_primary_key',
                        SchemaInterface::PRIMARY_KEY => 'uuid',
                        SchemaInterface::COLUMNS => ['uuid', 'email', 'balance'],
                        SchemaInterface::TYPECAST => [
                            'uuid' => [UuidPrimaryKey::class, 'typecast'],
                        ],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [],
                    ],
                ]
            )
        );
    }

    public function testCreate(): void
    {
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey(Uuid::uuid4()->toString()), 'hello@world.com', 500);

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);
    }

    public function testFetchData(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid), 'hello@world.com', 500);

        $this->save($e);

        $this->assertEquals($uuid, (string)$e->getID());

        $this->orm = $this->orm->withHeap(new Heap());
        $result = (new Select($this->orm, UserWithUUIDPrimaryKey::class))->fetchData();

        $this->assertInstanceOf(UuidPrimaryKey::class, $result[0]['uuid']);
        $this->assertEquals((string)$e->getID(), (string)$result[0]['uuid']);
    }

    public function testUpdate(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid), 'hello@world.com', 500);

        $this->save($e);

        $this->assertEquals($uuid, (string)$e->getID());

        $this->orm = $this->orm->withHeap(new Heap());
        $result = (new Select($this->orm, UserWithUUIDPrimaryKey::class))->fetchOne();

        $this->assertInstanceOf(UuidPrimaryKey::class, $result->getID());
        $this->assertEquals((string)$e->getID(), (string)$result->getID());

        $result->setEmail('new-mail@test.loc');

        $this->save($result);

        $selector = new Select($this->orm->withHeap(new Heap()), UserWithUUIDPrimaryKey::class);
        $result2 = $selector->fetchOne();

        $this->assertInstanceOf(UuidPrimaryKey::class, $result2->getID());
        $this->assertEquals($result->getEmail(), $result2->getEmail());
    }
}
