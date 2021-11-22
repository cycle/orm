<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\UserWithUUIDPrimaryKey;
use Cycle\ORM\Tests\Fixtures\UuidPrimaryKey;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Ramsey\Uuid\Uuid;

abstract class UUIDTypehintTest extends BaseTest
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
                        Schema::ROLE => 'user_with_uuid_primary_key',
                        Schema::MAPPER => Mapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'user_with_uuid_primary_key',
                        Schema::PRIMARY_KEY => 'uuid',
                        Schema::COLUMNS => ['uuid', 'email', 'balance'],
                        Schema::TYPECAST => [
                            'uuid' => [UuidPrimaryKey::class, 'typecast'],
                        ],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [],
                    ],
                ]
            )
        );
    }

    public function testCreate(): void
    {
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey(Uuid::uuid4()->toString()), 'hello@world.com', 500);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testFetchData(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid), 'hello@world.com', 500);

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->assertEquals($uuid, (string)$e->getID());

        $this->orm = $this->withHeap(new Heap());
        $selector = new Select($this->orm, UserWithUUIDPrimaryKey::class);
        $result = $selector->fetchData();

        $this->assertInstanceOf(UuidPrimaryKey::class, $result[0]['uuid']);
        $this->assertEquals((string)$e->getID(), (string)$result[0]['uuid']);
    }

    public function testUpdate(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid), 'hello@world.com', 500);

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->assertEquals($uuid, (string)$e->getID());

        $this->orm = $this->withHeap(new Heap());
        $selector = new Select($this->orm, UserWithUUIDPrimaryKey::class);
        $result = $selector->fetchOne();

        $this->assertInstanceOf(UuidPrimaryKey::class, $result->getID());
        $this->assertEquals((string)$e->getID(), (string)$result->getID());

        $result->setEmail('new-mail@test.loc');

        $tr = new Transaction($this->orm);
        $tr->persist($result);
        $tr->run();

        $selector = new Select($this->withHeap(new Heap()), UserWithUUIDPrimaryKey::class);
        $result2 = $selector->fetchOne();

        $this->assertInstanceOf(UuidPrimaryKey::class, $result2->getID());
        $this->assertEquals($result->getEmail(), $result2->getEmail());
    }
}
