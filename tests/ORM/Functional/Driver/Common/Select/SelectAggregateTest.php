<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class SelectAggregateTest extends BaseTest
{
    use TableTrait;

    public function testAvg(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->avg('balance');

        $this->assertEquals(150, (float) $result);
    }

    public function testAvgWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('id', 1)->avg('balance');

        $this->assertEquals(100, (float) $result);
    }

    public function testMin(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->min('balance');

        $this->assertEquals(100, (float) $result);
    }

    public function testMinWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('balance', '>', 100)->min('balance');

        $this->assertEquals(200, (float) $result);
    }

    public function testMax(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->max('balance');

        $this->assertEquals(200, (float) $result);
    }

    public function testMaxWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('balance', '<', 200)->max('balance');

        $this->assertEquals(100, (float) $result);
    }

    public function testSum(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->sum('balance');

        $this->assertEquals(300, (float) $result);
    }

    public function testSumWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->where('id', 1)->sum('balance');

        $this->assertEquals(100, (float) $result);
    }

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
