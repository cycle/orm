<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Pagination\Paginator;

abstract class PaginateTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        for ($i = 0; $i < 100; $i++) {

            $this->getDatabase()->table('user')->insertMultiple(['email', 'balance'], [
                [$i . '@world.com', $i * 100],
            ]);
        }

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFetchData()
    {
        $selector = new Select($this->orm, User::class);

        $this->assertSame(100, $selector->count());
    }

    public function testPaginate()
    {
        $selector = new Select($this->orm, User::class);

        (new Paginator(10))->paginate($selector);

        $this->assertCount(10, $selector->fetchData());
    }
}