<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;

abstract class ORMTest extends BaseTest
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

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ALIAS       => 'user',
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

    public function testORMGet()
    {
        $this->assertNull($this->orm->get(User::class, 1, false));
        $this->assertInstanceOf(User::class, $this->orm->get(User::class, 1, true));

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $this->orm->get(User::class, 1));
        $this->assertNumReads(0);
    }

    public function testORMGetByRole()
    {
        $this->assertNull($this->orm->get('user', 1, false));
        $this->assertInstanceOf(User::class, $this->orm->get('user', 1, true));

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $this->orm->get('user', 1));
        $this->assertNumReads(0);
    }
}