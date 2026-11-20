<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class SelectAggregateTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testAvg(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->avg('balance');

        \Testo\Assert::equals((float) $result, 150);
    }

    #[\Testo\Test]
    public function testAvgWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('id', 1)->avg('balance');

        \Testo\Assert::equals((float) $result, 100);
    }

    #[\Testo\Test]
    public function testMin(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->min('balance');

        \Testo\Assert::equals((float) $result, 100);
    }

    #[\Testo\Test]
    public function testMinWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('balance', '>', 100)->min('balance');

        \Testo\Assert::equals((float) $result, 200);
    }

    #[\Testo\Test]
    public function testMax(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->max('balance');

        \Testo\Assert::equals((float) $result, 200);
    }

    #[\Testo\Test]
    public function testMaxWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('balance', '<', 200)->max('balance');

        \Testo\Assert::equals((float) $result, 100);
    }

    #[\Testo\Test]
    public function testSum(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->sum('balance');

        \Testo\Assert::equals((float) $result, 300);
    }

    #[\Testo\Test]
    public function testSumWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('id', 1)->sum('balance');

        \Testo\Assert::equals((float) $result, 100);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id_int' => 'primary',
            'email_str' => 'string',
            'balance_float' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email_str', 'balance_float'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id_int', 'email' => 'email_str', 'balance' => 'balance_float'],
                Schema::SCHEMA => [],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'balance' => 'float',
                ],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
