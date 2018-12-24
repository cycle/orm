<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;

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
                Schema::ALIAS       => 'user',
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

        $this->assertDiff($this->a, $result[0]->time_created, 0);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertSame(2, $result[1]->id);
        $this->assertSame('another@world.com', $result[1]->email);
        $this->assertSame(200.0, $result[1]->balance);
        $this->assertInstanceOf(\DateTimeInterface::class, $result[1]->time_created);
        $this->assertSame('UTC', $result[1]->time_created->getTimezone()->getName());

        $this->assertDiff($this->b, $result[1]->time_created, 0);
    }

    /**
     * @param \DateTimeInterface $a
     * @param \DateTimeInterface $b
     * @param int                $max
     */
    protected function assertDiff(\DateTimeInterface $a, \DateTimeInterface $b, int $max)
    {
        $diff = abs($a->getTimestamp() - $b->getTimestamp());
        $this->assertTrue($diff <= $max, 'Invalid time internal');
    }
}