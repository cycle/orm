<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Tests\Fixtures\TimestampedMapper;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

abstract class AutoTimestampsTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'         => 'primary',
            'email'      => 'string',
            'balance'    => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => TimestampedMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance', 'created_at', 'updated_at'],
                Schema::TYPECAST    => [
                    'id'         => 'int',
                    'balance'    => 'float',
                    'created_at' => 'datetime',
                    'updated_at' => 'datetime'
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testCreate()
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $data = $s->fetchData();

        $this->assertNotNull($data[0]['created_at']);
        $this->assertNotNull($data[0]['updated_at']);

        $this->assertInstanceOf(\DateTimeInterface::class, $data[0]['created_at']);
        $this->assertInstanceOf(\DateTimeInterface::class, $data[0]['updated_at']);
    }

    public function testNoWrites()
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $u = $s->fetchOne();

        $this->captureWriteQueries();
        (new Transaction($orm))->persist($u)->run();
        $this->assertNumWrites(0);
    }

    public function testUpdate()
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $updatedAt = $s->fetchData()[0]['updated_at'];

        $u = $s->fetchOne();

        $u->balance = 200;

        sleep(1);

        $this->captureWriteQueries();
        (new Transaction($orm))->persist($u)->run();
        $this->assertNumWrites(1);

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $updatedAt2 = $s->fetchData()[0]['updated_at'];

        $this->assertNotEquals($updatedAt, $updatedAt2);
    }
}