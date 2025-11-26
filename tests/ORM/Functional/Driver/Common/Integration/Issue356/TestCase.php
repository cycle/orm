<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class TestCase extends BaseTest
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

    public function testSelectAll(): void
    {
        // Eager load morphed relation for multiple entities
        $this->captureReadQueries();
        $logs = (new Select($this->orm, Entity\LogRecord::class))
            ->load('actor')
            ->fetchAll();
        $this->assertNumReads(2);

        // Check result
        $this->captureReadQueries();
        foreach ($logs as $log) {
            $this->assertInstanceOf(Entity\LogRecord::class, $log);
            self::assertInstanceOf(Entity\Actor::class, $log->actor);
        }
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
            'id' => 'primary', // autoincrement
            'message' => 'string',
            'actor_id' => 'int,nullable',
            'actor_type' => 'string,nullable',
            'created_at' => 'datetime',
        ]);

        $this->makeTable(Entity\User::ROLE, [
            'id' => 'primary',
            'name' => 'string',
            'created_at' => 'datetime',
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
            ['name'],
            [
                ['user-1'],
                ['user-2'],
                ['user-3'],
                ['user-4'],
                ['user-5'],
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
            ['message', 'actor_type', 'actor_id', 'created_at'],
            [
                ['log-1 for user-1', Entity\User::ROLE, 1, new \DateTimeImmutable()],
                ['log-2 for user-2', Entity\User::ROLE, 2, new \DateTimeImmutable()],
                ['log-3 for tenant-1', Entity\Tenant::ROLE, 1, new \DateTimeImmutable()],
                ['log-4 for tenant-2', Entity\Tenant::ROLE, 2, new \DateTimeImmutable()],
                ['log-5 for user-3', Entity\User::ROLE, 3, new \DateTimeImmutable()],
            ],
        );
    }
}
