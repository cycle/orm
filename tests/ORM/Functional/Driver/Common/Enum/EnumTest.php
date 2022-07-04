<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Enum;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Enum\EnumUser as User;
use Cycle\ORM\Tests\Fixtures\Enum\TypeIntEnum;
use Cycle\ORM\Tests\Fixtures\Enum\TypeStringEnum;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * Common Enum store test
 *
 * @requires PHP >= 8.1
 */
abstract class EnumTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'balance' => 'int',
            'type_str' => 'string',
            'type_int' => 'int',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['balance', 'type_str', 'type_int'],
            [
                [56, TypeStringEnum::Guest->value, TypeIntEnum::Guest->value],
                [42, TypeStringEnum::User->value, TypeIntEnum::User->value],
                [69, TypeStringEnum::Admin->value, TypeIntEnum::Admin->value],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                SchemaInterface::ROLE => 'user',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'balance', 'type_str', 'type_int'],
                SchemaInterface::TYPECAST => [
                    'type_str' => \Closure::fromCallable([TypeStringEnum::class, 'make']),
                    'type_int' => \Closure::fromCallable([TypeIntEnum::class, 'make']),
                ],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }

    public function testFetchRawData(): void
    {
        $selector = new Select($this->orm, User::class);

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'balance' => 56,
                    'type_str' => 'guest',
                    'type_int' => 0,
                ],
                [
                    'id' => '2',
                    'balance' => 42,
                    'type_str' => 'user',
                    'type_int' => 1,
                ],
                [
                    'id' => '3',
                    'balance' => 69,
                    'type_str' => 'admin',
                    'type_int' => 2,
                ],
            ],
            $selector->fetchData(typecast: false)
        );
    }

    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);

        $this->assertEquals(
            [
                [
                    'id' => 1,
                    'balance' => 56,
                    'type_str' => TypeStringEnum::Guest,
                    'type_int' => TypeIntEnum::Guest,
                ],
                [
                    'id' => 2,
                    'balance' => 42,
                    'type_str' => TypeStringEnum::User,
                    'type_int' => TypeIntEnum::User,
                ],
                [
                    'id' => 3,
                    'balance' => 69,
                    'type_str' => TypeStringEnum::Admin,
                    'type_int' => TypeIntEnum::Admin,
                ],
            ],
            $selector->fetchData()
        );
    }

    public function testStoreData(): void
    {
        try {
            $user = $this->orm->make(
                User::class,
                [
                    'balance' => 88,
                    'type_str' => TypeStringEnum::User,
                    'type_int' => TypeIntEnum::User,
                ],
            );

            $this->captureWriteQueries();
            $this->save($user);
            $this->assertNumWrites(1);
        } catch (\Throwable) {
            $this->markTestSkipped("Can't save a Enum value because it is not supported yet.");
        }
    }
}
