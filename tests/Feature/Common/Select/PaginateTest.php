<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;
use Spiral\Pagination\Paginator;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class PaginateTest extends OrmTestCase
{
    #[Test]
    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);

        Assert::same($selector->count(), 100);
    }

    #[Test]
    public function testPaginate(): void
    {
        $selector = new Select($this->orm, User::class);

        (new Paginator(10))->paginate($selector);

        Assert::count($selector->fetchData(), 10);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        for ($i = 0; $i < 100; $i++) {
            $this->getDatabase()->table('user')->insertMultiple(['email', 'balance'], [
                [$i . '@world.com', $i * 100],
            ]);
        }

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
