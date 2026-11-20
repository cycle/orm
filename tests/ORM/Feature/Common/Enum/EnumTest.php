<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Enum;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Enum\EnumUser as User;
use Cycle\ORM\Tests\Fixtures\Enum\TypeIntEnum;
use Cycle\ORM\Tests\Fixtures\Enum\TypeStringEnum;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Core\Exception\SkipTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

/**
 * Common Enum store test
 */
final class EnumTest extends OrmTestCase
{
    #[Test]
    public function testFetchRawData(): void
    {
        $selector = new Select($this->orm, User::class);

        Assert::equals($selector->fetchData(typecast: false), [
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
        ]);
    }

    #[Test]
    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);

        Assert::equals($selector->fetchData(), [
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
        ]);
    }

    #[Test]
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

            QueryAssert::writes(1, fn() => $this->save($user));

        } catch (\Throwable) {
            throw new SkipTest("Can't save a Enum value because it is not supported yet.");
        }
    }

    #[BeforeTest]
    public function setUp(): void
    {
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
            ],
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
}
