<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

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
                'enum_string' => 'string',
                'enum_int' => 'int',
            ]
        );
        $this->getDatabase()->table('user')->insertMultiple(
            ['balance', 'enum_string', 'enum_int'],
            [
                [100, TypeStringEnum::Admin->value, TypeIntEnum::Admin->value],
                [200, TypeStringEnum::Guest->value, TypeIntEnum::Guest->value],
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
                        SchemaInterface::COLUMNS => ['id', 'balance', 'enum_string', 'enum_int'],
                        SchemaInterface::TYPECAST => [
                            'id' => 'int',
                            'balance' => 'int',
                            'enum_string' => TypeStringEnum::class,
                            'enum_int' => TypeIntEnum::class,
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
            \array_column($result, 'enum_string')
        );
        $this->assertSame(
            [TypeIntEnum::Admin, TypeIntEnum::Guest],
            \array_column($result, 'enum_int')
        );
    }

    public function testCreate(): void
    {
        $e = new User();
        $e->balance = 304;
        $e->enum_string = TypeStringEnum::Admin;
        $e->enum_int = TypeIntEnum::Admin;

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
        $e->enum_string = TypeStringEnum::Admin;
        $e->enum_int = TypeIntEnum::Admin;

        $this->save($e);

        $this->assertSame(TypeStringEnum::Admin, $e->enum_string);
        $this->assertSame(TypeIntEnum::Admin, $e->enum_int);
        $this->orm->getHeap()->clean();
        $result = (new Select($this->orm, User::class))->wherePK($e->id)->fetchOne();

        $this->assertSame(TypeStringEnum::Admin, $result->enum_string);
        $this->assertSame(TypeIntEnum::Admin, $result->enum_int);
    }

    public function testUpdate(): void
    {
        $e = new User();
        $e->balance = 304;
        $e->enum_string = TypeStringEnum::Admin;
        $e->enum_int = TypeIntEnum::Admin;

        $this->save($e);

        $this->orm->getHeap()->clean();
        /** @var User $result */
        $result = (new Select($this->orm, User::class))->fetchOne();

        $this->assertSame(TypeStringEnum::Admin, $result->enum_string);
        $this->assertSame(TypeIntEnum::Admin, $result->enum_int);

        $result->enum_string = TypeStringEnum::Guest;
        $result->enum_int = TypeIntEnum::Guest;

        $this->save($result);

        $this->orm->getHeap()->clean();
        $selector = new Select($this->orm, User::class);
        $result2 = $selector->fetchOne();

        $this->assertSame(TypeStringEnum::Guest, $result2->enum_string);
        $this->assertSame(TypeIntEnum::Guest, $result2->enum_int);
    }
}
