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

    public function testSelectHasMany(): void
    {
        // Get entity
        $group = (new Select($this->orm, TargetGroup::class))
            ->load('targets')
            ->where('id', 1)
            ->fetchOne();

        self::assertCount(3, $group->getTargets());
    }

    public function testSelectManyManyToMany(): void
    {
        // Get entity
        $group = (new Select($this->orm, TargetGroup::class))
            ->load('manyTargets')
            ->where('id', 1)
            ->fetchOne();

        self::assertCount(2, $group->getManyTargets());
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('target_groups', [
            'id' => 'primary',
            'name' => 'string',
        ]);

        $this->makeTable('targets', [
            'id' => 'primary', // autoincrement
            'target_group_id' => 'int',
            'monitor_name' => 'string',
        ]);
        $this->makeFK('targets', 'target_group_id', 'target_groups', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('ping_monitors', [
            'id' => 'int',
            'hostname' => 'string',
            'monitor_interval' => 'int',
        ]);
        $this->makeFK('ping_monitors', 'id', 'targets', 'id', 'NO ACTION', 'NO ACTION');


        $this->makeTable('pivots', [
            'target_id' => 'int',
            'target_group_id' => 'int',
            'hash' => 'string',
        ]);
        $this->makeFK('pivots', 'target_id', 'targets', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('pivots', 'target_group_id', 'target_groups', 'id', 'NO ACTION', 'NO ACTION');

        // $this->makeTable('pivot_children', [
        //     'target_id' => 'int',
        //     'target_group_id' => 'int',
        //     'rate' => 'int',
        // ]);
        // $this->makeFK('pivot_children', 'target_id', 'pivots', 'target_id', 'CASCADE', 'CASCADE');
        // $this->makeFK('pivot_children', 'target_group_id', 'pivots', 'target_group_id', 'CASCADE', 'CASCADE');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('target_groups')->insertMultiple(
            ['name'],
            [
                ['Group 1'],
                ['Group 2'],
            ],
        );
        $this->getDatabase()->table('targets')->insertMultiple(
            ['target_group_id', 'monitor_name'],
            [
                [1, 'foo-monitor'],
                [1, 'bar-monitor'],
                [1, 'baz-monitor'],
                [2, 'fiz-monitor'],
                [2, 'hex-monitor'],
            ],
        );
        $this->getDatabase()->table('ping_monitors')->insertMultiple(
            ['id', 'hostname', 'monitor_interval'],
            [
                [1, 'foo.foo', 10],
                [2, 'bar.bar', 15],
            ],
        );
        $this->getDatabase()->table('pivots')->insertMultiple(
            ['target_id', 'target_group_id', 'hash'],
            [
                [1, 1, '1/1'],
                [2, 1, '2/1'],
                [3, 2, '3/2'],
                [4, 2, '4/2'],
                [5, 2, '5/2'],
            ],
        );
        // $this->getDatabase()->table('pivot_children')->insertMultiple(
        //     ['target_id', 'target_group_id', 'rate'],
        //     [
        //         [2, 1, 1],
        //         [3, 2, 2],
        //     ],
        // );
    }
}
