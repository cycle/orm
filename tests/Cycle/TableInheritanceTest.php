<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Tests\Fixtures\Admin;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

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

        $this->orm = $this->withSchema(new Schema([
            User::class  => [
                Schema::ALIAS       => 'user',
                Schema::CHILDREN    => [
                    'admin' => Admin::class
                ],
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', '_type', 'email', 'balance', 'permissions'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
            Admin::class => [Schema::ALIAS => User::class,]
        ]));
    }

    public function testFetchData()
    {
        $selector = new Select($this->orm, User::class);

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
        $selector = new Select($this->orm, User::class);
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

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($a);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $this->assertInstanceOf(User::class, $selector->wherePK(4)->fetchOne());

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $this->assertNotInstanceOf(Admin::class, $selector->wherePK(4)->fetchOne());

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $this->assertInstanceOf(Admin::class, $selector->wherePK(5)->fetchOne());
    }
}