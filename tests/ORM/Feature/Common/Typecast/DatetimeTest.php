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
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Common\Attribute\AssertMethod;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class DatetimeTest extends OrmTestCase
{
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

    #[Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        Assert::instanceOf($result[0], User::class);
        Assert::same($result[0]->id, 1);
        Assert::same($result[0]->email, 'hello@world.com');
        Assert::same($result[0]->balance, 100.0);
        Assert::instanceOf($result[0]->time_created, \DateTimeInterface::class);
        Assert::same($result[0]->time_created->getTimezone()->getName(), 'UTC');

        $this->assertSameTimestamp($this->a, $result[0]->time_created, 0);

        Assert::instanceOf($result[1], User::class);
        Assert::same($result[1]->id, 2);
        Assert::same($result[1]->email, 'another@world.com');
        Assert::same($result[1]->balance, 200.0);
        Assert::instanceOf($result[1]->time_created, \DateTimeInterface::class);
        Assert::same($result[1]->time_created->getTimezone()->getName(), 'UTC');

        $this->assertSameTimestamp($this->b, $result[1]->time_created, 0);
    }

    #[Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        QueryAssert::writes(0, function () use ($result): void {
            $tr = new Transaction($this->orm);
            $tr->persist($result);
            $tr->run();
        });
    }

    #[Test]
    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->time_created = new \DateTimeImmutable('tomorrow 10am');

        QueryAssert::writes(1, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });
        QueryAssert::writes(0, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });

        Assert::equals($e->id, 3);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        Assert::equals($result->balance, 300);
        $this->assertSameTimestamp($e->time_created, $result->time_created, 0);
    }

    #[Test]
    public function testUpdate(): void
    {
        $e = $this->orm->get('user', ['id' => 1]);
        $e->time_created = new \DateTimeImmutable('tomorrow 10pm');

        QueryAssert::writes(1, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });
        QueryAssert::writes(0, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 1)->fetchOne();
        $this->assertSameTimestamp($e->time_created, $result->time_created, 0);
    }

    #[BeforeTest]
    public function setUp(): void
    {
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

    #[AssertMethod]
    protected function assertSameTimestamp(\DateTimeInterface $a, \DateTimeInterface $b, int $max): void
    {
        $diff = abs($a->getTimestamp() - $b->getTimestamp());
        Assert::true($diff <= $max, 'Invalid time internal');
    }
}
