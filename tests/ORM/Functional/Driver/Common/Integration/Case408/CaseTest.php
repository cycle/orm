<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Entity\TargetGroup;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testSelect(): void
    {
        // Get entity
        $user = (new Select($this->orm, TargetGroup::class))
            ->load('targets',
                // [
                //     'where' => function (/*QueryBuilder*/ $qb) use ($pingMonitorId) {
                //         $qb->where('id', $pingMonitorId);
                //     },
                // ],
            )
            ->where('id', 1)
            ->fetchOne();

        self::assertTrue(true, 'No exception thrown');
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('targets', [
            'id' => 'primary', // autoincrement
            'target_group_id' => 'int',
            'monitor_name' => 'string',
        ]);

        $this->makeTable('ping_monitors', [
            'id' => 'int',
            'hostname' => 'string',
            'monitor_interval' => 'int',
        ]);
        $this->makeFK('ping_monitors', 'id', 'targets', 'id', 'CASCADE', 'CASCADE');

        $this->makeTable('target_groups', [
            'id' => 'primary',
            'name' => 'string',
        ]);
        $this->makeFK('targets', 'target_group_id', 'target_groups', 'id', 'CASCADE', 'CASCADE');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('targets')->insertMultiple(
            ['target_group_id', 'monitor_name'],
            [
                ['1', 'foo-monitor'],
                ['1', 'bar-monitor'],
                ['1', 'baz-monitor'],
            ],
        );
        $this->getDatabase()->table('ping_monitors')->insertMultiple(
            ['id', 'hostname', 'monitor_interval'],
            [
                [1, 'foo.foo', 10],
                [2, 'bar.bar', 15],
            ],
        );
        $this->getDatabase()->table('target_groups')->insertMultiple(
            ['id', 'name'],
            [
                [1, 'Group 1'],
            ],
        );
    }
}
