<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Admin;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class TableInheritanceTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
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
                Schema::ROLE        => 'user',
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
            Admin::class => [Schema::ROLE => User::class]
        ]));
    }

    public function testFetchData(): void
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

    public function testIterate(): void
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

    public function testStoreNormalAndInherited(): void
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
