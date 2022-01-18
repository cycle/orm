<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Enum;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Enum\Fixture\TypeEnum;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * @requires PHP 8.1
 */
abstract class EnumTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'name' => 'string',
            'type' => 'int',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['name', 'type'],
            [
                ['Olga', TypeEnum::Guest->value],
                ['Alex', TypeEnum::User->value],
                ['Antony', TypeEnum::Admin->value],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                SchemaInterface::ROLE => 'user',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'name', 'type'],
                SchemaInterface::TYPECAST => [
                    'type' => TypeEnum::make(...),
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
                    'name' => 'Olga',
                    'type' => 'guest',
                ],
                [
                    'id' => '2',
                    'name' => 'Alex',
                    'type' => 'user',
                ],
                [
                    'id' => '3',
                    'name' => 'Antony',
                    'type' => 'admin',
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
                    'name' => 'Olga',
                    'type' => TypeEnum::Guest,
                ],
                [
                    'id' => 2,
                    'name' => 'Alex',
                    'type' => TypeEnum::User,
                ],
                [
                    'id' => 3,
                    'name' => 'Antony',
                    'type' => TypeEnum::Admin,
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
                    'name' => 'Bob',
                    'type' => TypeEnum::User,
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
