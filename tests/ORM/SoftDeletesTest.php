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
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\NotDeletedConstrain;
use Cycle\ORM\Tests\Fixtures\SoftDeletedMapper;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class SoftDeletesTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'         => 'primary',
            'email'      => 'string',
            'balance'    => 'float',
            'deleted_at' => 'datetime,null',
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => SoftDeletedMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance', 'deleted_at'],
                Schema::TYPECAST    => [
                    'id'         => 'int',
                    'balance'    => 'float',
                    'deleted_at' => 'datetime'
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
                Schema::CONSTRAIN   => NotDeletedConstrain::class
            ]
        ]));
    }

    public function testCreate(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $data = $s->fetchData();

        $this->assertNull($data[0]['deleted_at']);
    }

    public function testDelete(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $u = $s->fetchOne();

        (new Transaction($orm))->delete($u)->run();

        // must be deleted
        $orm = $this->orm->withHeap(new Heap());
        $s = $orm->getRepository(User::class);
        $this->assertNull($s->findOne());

        $this->assertSame($s, $orm->getRepository(User::class));

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $s->constrain(new NotDeletedConstrain());
        $this->assertNull($s->fetchOne());

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $s->constrain(null);
        $this->assertNotNull($s->fetchOne());
    }
}
