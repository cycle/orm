<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Typecast;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

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

    #[\Testo\Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        \Testo\Assert::instanceOf($result[0], User::class);
        \Testo\Assert::same($result[0]->id, 1);
        \Testo\Assert::same($result[0]->email, 'hello@world.com');
        \Testo\Assert::same($result[0]->balance, 100.0);
        \Testo\Assert::instanceOf($result[0]->time_created, \DateTimeInterface::class);
        \Testo\Assert::same($result[0]->time_created->getTimezone()->getName(), 'UTC');

        $this->assertSameTimestamp($this->a, $result[0]->time_created, 0);

        \Testo\Assert::instanceOf($result[1], User::class);
        \Testo\Assert::same($result[1]->id, 2);
        \Testo\Assert::same($result[1]->email, 'another@world.com');
        \Testo\Assert::same($result[1]->balance, 200.0);
        \Testo\Assert::instanceOf($result[1]->time_created, \DateTimeInterface::class);
        \Testo\Assert::same($result[1]->time_created->getTimezone()->getName(), 'UTC');

        $this->assertSameTimestamp($this->b, $result[1]->time_created, 0);
    }

    #[\Testo\Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($result);
        $tr->run();
        $this->assertNumWrites(0);
    }

    #[\Testo\Test]
    public function testStore(): void
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

        \Testo\Assert::equals($e->id, 3);

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        \Testo\Assert::equals($result->balance, 300);
        $this->assertSameTimestamp($e->time_created, $result->time_created, 0);
    }

    #[\Testo\Test]
    public function testUpdate(): void
    {
        $e = $this->orm->get('user', ['id' => 1]);
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

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'time_created' => 'datetime',
            'balance' => 'float',
        ]);

        $this->now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Minsk'));

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'time_created', 'balance'],
            [
                ['hello@world.com', $this->a = $this->now->add(new \DateInterval('PT1H')), 100],
                ['another@world.com', $this->b = $this->now->add(new \DateInterval('PT2H')), 200],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                SchemaInterface::ROLE => 'user',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'email', 'time_created', 'balance'],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'balance' => 'float',
                    'time_created' => 'datetime',
                ],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }

    #[\Testo\Common\Attribute\AssertMethod]
    protected function assertSameTimestamp(\DateTimeInterface $a, \DateTimeInterface $b, int $max): void
    {
        $diff = abs($a->getTimestamp() - $b->getTimestamp());
        \Testo\Assert::true($diff <= $max, 'Invalid time internal');
    }
}
