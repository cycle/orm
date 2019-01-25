<?php
declare(strict_types=1);/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

abstract class DatetimeTest extends BaseTest
{
    use TableTrait;

    /**
     * @var \DateTimeImmutable
     */
    protected $now;

    /**
     * @var \DateTimeImmutable
     */
    protected $a;

    /**
     * @var \DateTimeImmutable
     */
    protected $b;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'           => 'primary',
            'email'        => 'string',
            'time_created' => 'datetime',
            'balance'      => 'float'
        ]);

        $this->now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Minsk'));

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'time_created', 'balance'],
            [
                ['hello@world.com', $this->a = $this->now->add(new \DateInterval('PT1H')), 100],
                ['another@world.com', $this->b = $this->now->add(new \DateInterval('PT2H')), 200],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'time_created', 'balance'],
                Schema::TYPECAST    => [
                    'id'           => 'int',
                    'balance'      => 'float',
                    'time_created' => 'datetime'
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFetchAll()
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('hello@world.com', $result[0]->email);
        $this->assertSame(100.0, $result[0]->balance);
        $this->assertInstanceOf(\DateTimeInterface::class, $result[0]->time_created);
        $this->assertSame('UTC', $result[0]->time_created->getTimezone()->getName());

        $this->assertSameTimestamp($this->a, $result[0]->time_created, 0);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertSame(2, $result[1]->id);
        $this->assertSame('another@world.com', $result[1]->email);
        $this->assertSame(200.0, $result[1]->balance);
        $this->assertInstanceOf(\DateTimeInterface::class, $result[1]->time_created);
        $this->assertSame('UTC', $result[1]->time_created->getTimezone()->getName());

        $this->assertSameTimestamp($this->b, $result[1]->time_created, 0);
    }

    public function testNoWrite()
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($result);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testStore()
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->time_created = new \DateTimeImmutable('tomorrow 10am');

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        $this->assertEquals(300, $result->balance);
        $this->assertSameTimestamp($e->time_created, $result->time_created, 0);
    }

    public function testUpdate()
    {
        $e = $this->orm->get('user', 1);
        $e->time_created = new \DateTimeImmutable('tomorrow 10pm');

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 1)->fetchOne();
        $this->assertSameTimestamp($e->time_created, $result->time_created, 0);
    }

    /**
     * @param \DateTimeInterface $a
     * @param \DateTimeInterface $b
     * @param int                $max
     */
    protected function assertSameTimestamp(\DateTimeInterface $a, \DateTimeInterface $b, int $max)
    {
        $diff = abs($a->getTimestamp() - $b->getTimestamp());
        $this->assertTrue($diff <= $max, 'Invalid time internal');
    }
}