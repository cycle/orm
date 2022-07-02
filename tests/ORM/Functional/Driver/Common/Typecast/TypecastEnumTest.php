<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Enum\Fixture\TypeStringEnum;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\EnumedUser as User;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * @requires PHP >= 8.1
 */
abstract class TypecastEnumTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'user',
            [
                'id' => 'primary',
                'balance' => 'int',
                'enum_type' => 'string',
            ]
        );
        $this->getDatabase()->table('user')->insertMultiple(
            ['balance', 'enum_type'],
            [
                [100, TypeStringEnum::Admin->value],
                [200, TypeStringEnum::Guest->value],
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    User::class => [
                        SchemaInterface::ROLE => 'user',
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'user',
                        SchemaInterface::PRIMARY_KEY => 'id',
                        SchemaInterface::COLUMNS => ['id', 'balance', 'enum_type'],
                        SchemaInterface::TYPECAST => [
                            'id' => 'int',
                            'balance' => 'int',
                            'enum_type' => TypeStringEnum::class,
                        ],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [],
                    ],
                ]
            )
        );
    }

    public function testFetchData(): void
    {
        $result = (new Select($this->orm, User::class))->fetchData();

        $this->assertSame(
            [TypeStringEnum::Admin, TypeStringEnum::Guest],
            \array_column($result, 'enum_type')
        );
    }

    public function testCreate(): void
    {
        $e = new User();
        $e->balance = 304;
        $e->enum_type = TypeStringEnum::Admin;

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);
    }

    public function testPushAndFetchData(): void
    {
        $e = new User();
        $e->balance = 304;
        $e->enum_type = TypeStringEnum::Admin;

        $this->save($e);

        $this->assertSame(TypeStringEnum::Admin, $e->enum_type);
        $this->orm->getHeap()->clean();
        $result = (new Select($this->orm, User::class))->wherePK($e->id)->fetchOne();

        $this->assertSame(TypeStringEnum::Admin, $result->enum_type);
    }

    public function testUpdate(): void
    {
        $e = new User();
        $e->balance = 304;
        $e->enum_type = TypeStringEnum::Admin;

        $this->save($e);

        $this->orm->getHeap()->clean();
        /** @var User $result */
        $result = (new Select($this->orm, User::class))->fetchOne();

        $this->assertSame(TypeStringEnum::Admin, $result->enum_type);

        $result->enum_type = TypeStringEnum::Guest;

        $this->save($result);

        $this->orm->getHeap()->clean();
        $selector = new Select($this->orm, User::class);
        $result2 = $selector->fetchOne();

        $this->assertSame(TypeStringEnum::Guest, $result2->enum_type);
    }
}
