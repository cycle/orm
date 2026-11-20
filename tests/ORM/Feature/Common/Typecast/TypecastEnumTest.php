<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Typecast;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Enum\EnumUser as User;
use Cycle\ORM\Tests\Fixtures\Enum\TypeIntEnum;
use Cycle\ORM\Tests\Fixtures\Enum\TypeStringEnum;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

/**
 */
class TypecastEnumTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testFetchData(): void
    {
        $result = (new Select($this->orm, User::class))->fetchData();

        \Testo\Assert::same(\array_column($result, 'enum_string'), [TypeStringEnum::Admin, TypeStringEnum::Guest]);
        \Testo\Assert::same(\array_column($result, 'enum_int'), [TypeIntEnum::Admin, TypeIntEnum::Guest]);
    }

    #[\Testo\Test]
    public function testCreate(): void
    {
        $e = new User();
        $e->balance = 304;
        $e->enum_string = TypeStringEnum::Admin;
        $e->enum_int = TypeIntEnum::Admin;
        QueryAssert::writes(1, fn() => $this->save($e));
        QueryAssert::writes(0, fn() => $this->save($e));
    }

    #[\Testo\Test]
    public function testPushAndFetchData(): void
    {
        $e = new User();
        $e->balance = 304;
        $e->enum_string = TypeStringEnum::Admin;
        $e->enum_int = TypeIntEnum::Admin;

        $this->save($e);

        \Testo\Assert::same($e->enum_string, TypeStringEnum::Admin);
        \Testo\Assert::same($e->enum_int, TypeIntEnum::Admin);
        $this->orm->getHeap()->clean();
        $result = (new Select($this->orm, User::class))->wherePK($e->id)->fetchOne();

        \Testo\Assert::same($result->enum_string, TypeStringEnum::Admin);
        \Testo\Assert::same($result->enum_int, TypeIntEnum::Admin);
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($result->enum_string, TypeStringEnum::Admin);
        \Testo\Assert::same($result->enum_int, TypeIntEnum::Admin);

        $result->enum_string = TypeStringEnum::Guest;
        $result->enum_int = TypeIntEnum::Guest;

        $this->save($result);

        $this->orm->getHeap()->clean();
        $selector = new Select($this->orm, User::class);
        $result2 = $selector->fetchOne();

        \Testo\Assert::same($result2->enum_string, TypeStringEnum::Guest);
        \Testo\Assert::same($result2->enum_int, TypeIntEnum::Guest);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable(
            'user',
            [
                'id' => 'primary',
                'balance' => 'int',
                'enum_string' => 'string',
                'enum_int' => 'int',
            ],
        );
        $this->getDatabase()->table('user')->insertMultiple(
            ['balance', 'enum_string', 'enum_int'],
            [
                [100, TypeStringEnum::Admin->value, TypeIntEnum::Admin->value],
                [200, TypeStringEnum::Guest->value, TypeIntEnum::Guest->value],
            ],
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
                ],
            ),
        );
    }
}
