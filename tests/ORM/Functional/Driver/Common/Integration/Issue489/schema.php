<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue489\Entity\User;

return [
    'user' => [
        Schema::ENTITY => User::class,
        Schema::SOURCE => Source::class,
        Schema::MAPPER => Mapper::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'user_id' => 'user_id',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => 'user_id',
                    Relation::OUTER_KEY => ['id'],
                    Relation::INVERSION => 'users',
                ],
            ],
            'users' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'user_id',
                    Relation::INVERSION => 'user',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'user_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
];
