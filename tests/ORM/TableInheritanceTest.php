<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Heap;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\Admin;
use Spiral\ORM\Tests\Fixtures\EntityMapper;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\UnitOfWork;

abstract class TableInheritanceTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'          => 'primary',
            '_type'       => 'string,nullable',
            'email'       => 'string',
            'balance'     => 'float',
            'permissions' => 'string,nullable'
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['_type', 'email', 'balance', 'permissions'],
            [
                ['user', 'hello@world.com', 100, ''],
                ['admin', 'another@world.com', 200, '*'],
                [null, 'third@world.com', 300, ''],
            ]
        );

        $this->orm = $this->orm->withSchema(new Schema([
            User::class  => [
                Schema::ALIAS       => 'user',
                Schema::CHILDREN    => [
                    'admin' => Admin::class
                ],
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', '_type', 'email', 'balance', 'permissions'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
            Admin::class => [
                Schema::ALIAS     => 'admin',
                Schema::EXTENDS   => User::class,
                Schema::SCHEMA    => [],
                Schema::RELATIONS => []
            ]
        ]));
    }

    public function testFetchData()
    {
        $selector = new Selector($this->orm, User::class);

        $this->assertEquals([
            [
                'id'          => 1,
                '_type'       => 'user',
                'email'       => 'hello@world.com',
                'balance'     => 100.0,
                'permissions' => ''
            ],
            [
                'id'          => 2,
                '_type'       => 'admin',
                'email'       => 'another@world.com',
                'balance'     => 200.0,
                'permissions' => '*'
            ],
            [
                'id'          => 3,
                '_type'       => null,
                'email'       => 'third@world.com',
                'balance'     => 300.0,
                'permissions' => ''
            ],

        ], $selector->fetchData());
    }

    public function testIterate()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b, $c) = $selector->orderBy('id')->fetchAll();

        $this->assertInstanceOf(User::class, $a);
        $this->assertNotInstanceOf(Admin::class, $a);
        $this->assertInstanceOf(Admin::class, $b);
        $this->assertInstanceOf(User::class, $c);
        $this->assertNotInstanceOf(Admin::class, $c);

        $this->assertSame('*', $b->permissions);
    }

    public function testStoreNormalAndInherited()
    {
        $u = new User();
        $u->email = 'user@email.com';
        $u->balance = 100;

        $a = new Admin();
        $a->email = 'admin@email.com';
        $a->balance = 400;
        $a->permissions = '~';

        $tr = new UnitOfWork($this->orm);
        $tr->store($u);
        $tr->store($a);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $this->assertInstanceOf(User::class, $selector->wherePK(4)->fetchOne());

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $this->assertNotInstanceOf(Admin::class, $selector->wherePK(4)->fetchOne());

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $this->assertInstanceOf(Admin::class, $selector->wherePK(5)->fetchOne());
    }
}