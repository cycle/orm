<?php

declare(strict_types=1);

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case4\Entity\Node;

return [
    'node' => [
        Schema::ENTITY => Node::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'nodes',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'key' => 'key',
            'parent_id' => 'parent_id',
        ],
        Schema::RELATIONS => [
            'parent' => [
                Relation::TYPE => Relation::REFERS_TO,
                Relation::TARGET => 'node',
                Relation::SCHEMA => [
                    Relation::CASCADE => false,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => 'id',
                    Relation::OUTER_KEY => 'parent_id'
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
        ],
    ],
];
