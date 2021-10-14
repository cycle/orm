<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Mapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\TimestampedMapper;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class AutoTimestampsTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'user',
            [
                'id' => 'primary',
                'email' => 'string',
                'balance' => 'float',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    User::class => [
                        Schema::ROLE => 'user',
                        Schema::MAPPER => TimestampedMapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'user',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS => ['id', 'email', 'balance', 'created_at', 'updated_at'],
                        Schema::TYPECAST => [
                            'id' => 'int',
                            'balance' => 'float',
                            'created_at' => 'datetime',
                            'updated_at' => 'datetime',
                        ],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [],
                    ],
                ]
            )
        );
    }

    public function testCreate(): void
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

    public function testNoWrites(): void
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

    public function testUpdate(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        $this->save($u);

        $this->orm = $this->orm->withHeap(new Heap());
        $s = new Select($this->orm, User::class);

        $u = $s->fetchOne();

        $u->balance = 200;

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);
    }
}
