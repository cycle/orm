<?php

declare(strict_types=1);

use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Entity\PingMonitor;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Entity\Target;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Entity\TargetGroup;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\TypeCaster;

return [
    'target' => [
        Schema::ENTITY => Target::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'targets',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'target_group_id' => 'target_group_id',
            'monitorName' => 'monitor_name',
        ],
        Schema::RELATIONS => [],
        Schema::TYPECAST => [
            'id' => Type\TargetId::class,
            'target_group_id' => Type\TargetGroupId::class,
            'monitorName' => Type\MonitorName::class,
        ],
        Schema::TYPECAST_HANDLER => [TypeCaster::class, Typecast::class],
    ],

    'pingMonitor' => [
        Schema::ENTITY => PingMonitor::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'ping_monitors',
        Schema::PARENT => Target::class,
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'hostname' => 'hostname',
            'monitorInterval' => 'monitor_interval',
        ],
        Schema::RELATIONS => [],
        Schema::TYPECAST => [
            'id' => Type\PingMonitorId::class,
            'hostname' => Type\PublicHostname::class,
            'monitorInterval' => Type\MonitorInterval::class,
        ],
        Schema::TYPECAST_HANDLER => [TypeCaster::class, Typecast::class],
    ],

    'targetGroup' => [
        Schema::ENTITY => TargetGroup::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'target_groups',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
        ],
        Schema::RELATIONS => [
            'targets' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'target',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::COLLECTION_TYPE => 'doctrine',
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'target_group_id',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => Type\TargetGroupId::class,
            'name' => Type\TargetGroupName::class,
        ],
        Schema::TYPECAST_HANDLER => [TypeCaster::class, Cycle\ORM\Parser\Typecast::class],
    ],
];
