<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class SelectAggregateTest extends OrmTestCase
{
    #[Test]
    public function testAvg(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->avg('balance');

        Assert::equals((float) $result, 150);
    }

    #[Test]
    public function testAvgWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('id', 1)->avg('balance');

        Assert::equals((float) $result, 100);
    }

    #[Test]
    public function testMin(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->min('balance');

        Assert::equals((float) $result, 100);
    }

    #[Test]
    public function testMinWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('balance', '>', 100)->min('balance');

        Assert::equals((float) $result, 200);
    }

    #[Test]
    public function testMax(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->max('balance');

        Assert::equals((float) $result, 200);
    }

    #[Test]
    public function testMaxWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('balance', '<', 200)->max('balance');

        Assert::equals((float) $result, 100);
    }

    #[Test]
    public function testSum(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->sum('balance');

        Assert::equals((float) $result, 300);
    }

    #[Test]
    public function testSumWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('id', 1)->sum('balance');

        Assert::equals((float) $result, 100);
    }

    #[BeforeTest]
    public function setUp(): void
    {
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
