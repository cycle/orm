<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356;

use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * Test belongs to morphed loader
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testSelectOne(): void
    {
        // Eager load morphed relation
        $this->captureReadQueries();
        $log = (new Select($this->orm, Entity\LogRecord::class))
            ->load('actor')
            ->wherePK(1)
            ->fetchOne();
        $this->assertNumReads(2);

        // Check result
        $this->captureReadQueries();
        $this->assertInstanceOf(Entity\LogRecord::class, $log);
        $this->assertNumReads(0);
    }

    public function testUpdateOne(): void
    {
        // Eager load morphed relation
        $this->captureReadQueries();
        $log = (new Select($this->orm, Entity\LogRecord::class))
            ->wherePK(1)
            ->fetchOne();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect($log)
            ->load('actor')
            ->run();
        $this->assertNumReads(1);

        // Check result
        $this->captureReadQueries();
        $this->assertInstanceOf(Entity\Actor::class, $log->actor);
        $this->assertNumReads(0);
    }

    public function testSelectAll(): void
    {
        // Eager load morphed relation for multiple entities
        $this->captureReadQueries();
        $logs = (new Select($this->orm, Entity\LogRecord::class))
            ->load('actor')
            ->fetchAll();
        $this->assertNumReads(3);

        // Check result
        $this->captureReadQueries();
        foreach ($logs as $log) {
            $this->assertInstanceOf(Entity\LogRecord::class, $log);
            \in_array($log->lid, [9], true)
                ? self::assertNull($log->actor)
                : self::assertInstanceOf(Entity\Actor::class, $log->actor);
        }
        $this->assertNumReads(0);
    }

    public function testUpdateMany(): void
    {
        // Eager load morphed relation
        $this->captureReadQueries();
        /** @var list<Entity\LogRecord> $logs */
        $logs = (new Select($this->orm, Entity\LogRecord::class))
            ->fetchAll();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$logs)
            ->load('actor')
            ->run();
        // 2 queries: one for users, one for tenants
        $this->assertNumReads(2);

        // Check result
        $this->captureReadQueries();
        foreach ($logs as $log) {
            \in_array($log->lid, [9], true)
                ? self::assertNull($log->actor)
                : self::assertInstanceOf(Entity\Actor::class, $log->actor);
        }
        $this->assertNumReads(0);
    }

    public function testScoped(): void
    {
        // Eager load morphed relation
        $this->captureReadQueries();
        /** @var array<int, Entity\LogRecord> $logs */
        $logs = (new Select($this->orm, Entity\LogRecord::class))
            ->load('actor')
            ->wherePK(9, 10)
            ->orderBy('l_id')
            ->fetchAll();
        $this->assertNumReads(3);

        $this->assertCount(2, $logs);

        // Check result
        $this->captureReadQueries();
        self::assertNull($logs[0]->actor);
        self::assertInstanceOf(Entity\Tenant::class, $logs[1]->actor);
        $this->assertNumReads(0);
    }

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        $this->makeTable(Entity\LogRecord::ROLE, [
            'l_id' => 'primary', // autoincrement
            'l_message' => 'string',
            'l_actor_id' => 'int,nullable',
            'l_actor_type' => 'string,nullable',
            'l_created_at' => 'datetime',
        ]);

        $this->makeTable(Entity\User::ROLE, [
            // The columns order is matters here for testSelectAll purpose
            'u_active' => 'bool',
            'u_name' => 'string',
            'u_id' => 'primary',
            'u_created_at' => 'datetime',
        ]);

        $this->makeTable(Entity\Tenant::ROLE, [
            'id' => 'primary',
            'name' => 'string',
            'created_at' => 'datetime',
        ]);
    }

    private function fillData(): void
    {
        $this->getDatabase()->table(Entity\User::ROLE)->insertMultiple(
            ['u_name', 'u_active'],
            [
                ['user-1', true],
                ['user-2', true],
                ['user-3', true],
                ['user-4', true],
                ['user-5', false],
            ],
        );
        $this->getDatabase()->table(Entity\Tenant::ROLE)->insertMultiple(
            ['name'],
            [
                ['tenant-1'],
                ['tenant-2'],
                ['tenant-3'],
                ['tenant-4'],
                ['tenant-5'],
            ],
        );
        $this->getDatabase()->table(Entity\LogRecord::ROLE)->insertMultiple(
            ['l_message', 'l_actor_type', 'l_actor_id', 'l_created_at'],
            [
                ['log-1 for user-1', Entity\User::ROLE, 1, new \DateTimeImmutable()],
                ['log-2 for user-2', Entity\User::ROLE, 2, new \DateTimeImmutable()],
                ['log-3 for tenant-1', Entity\Tenant::ROLE, 1, new \DateTimeImmutable()],
                ['log-4 for tenant-2', Entity\Tenant::ROLE, 2, new \DateTimeImmutable()],
                ['log-5 for user-3', Entity\User::ROLE, 3, new \DateTimeImmutable()],
                ['log-6 for tenant-3', Entity\Tenant::ROLE, 3, new \DateTimeImmutable()],
                ['log-7 for user-4', Entity\User::ROLE, 1, new \DateTimeImmutable()],
                ['log-8 for tenant-4', Entity\Tenant::ROLE, 2, new \DateTimeImmutable()],
                ['log-9 for user-5', Entity\User::ROLE, 5, new \DateTimeImmutable()],
                ['log-10 for tenant-5', Entity\Tenant::ROLE, 5, new \DateTimeImmutable()],
            ],
        );
    }
}
