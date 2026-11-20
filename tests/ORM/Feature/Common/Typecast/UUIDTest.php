<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Typecast;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\UserWithUUIDPrimaryKey;
use Cycle\ORM\Tests\Fixtures\UuidPrimaryKey;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Ramsey\Uuid\Uuid;

abstract class UUIDTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
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

    #[\Testo\Test]
    public function testFetchData(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid), 'hello@world.com', 500);

        $this->save($e);

        \Testo\Assert::equals((string) $e->getID(), $uuid);

        $this->orm = $this->orm->withHeap(new Heap());
        $result = (new Select($this->orm, UserWithUUIDPrimaryKey::class))->fetchData();

        \Testo\Assert::instanceOf($result[0]['uuid'], UuidPrimaryKey::class);
        \Testo\Assert::equals((string) $result[0]['uuid'], (string) $e->getID());
    }

    #[\Testo\Test]
    public function testUpdate(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid), 'hello@world.com', 500);

        $this->save($e);

        \Testo\Assert::equals((string) $e->getID(), $uuid);

        $this->orm = $this->orm->withHeap(new Heap());
        $result = (new Select($this->orm, UserWithUUIDPrimaryKey::class))->fetchOne();

        \Testo\Assert::instanceOf($result->getID(), UuidPrimaryKey::class);
        \Testo\Assert::equals((string) $result->getID(), (string) $e->getID());

        $result->setEmail('new-mail@test.loc');

        $this->save($result);

        $selector = new Select($this->orm->withHeap(new Heap()), UserWithUUIDPrimaryKey::class);
        $result2 = $selector->fetchOne();

        \Testo\Assert::instanceOf($result2->getID(), UuidPrimaryKey::class);
        \Testo\Assert::equals($result2->getEmail(), $result->getEmail());
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'user_with_uuid_primary_key',
            [
                'uuid' => 'string(36),primary',
                'email' => 'string',
                'balance' => 'float',
            ],
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
                ],
            ),
        );
    }
}
