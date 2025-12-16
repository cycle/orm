<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356\ActiveScope;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356\Entity\Tenant;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356\Entity\LogRecord;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356\Entity\Actor;

return [
    LogRecord::ROLE => [
        Schema::ENTITY => LogRecord::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'log',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'message' => 'message',
            'actor_id' => 'actor_id',
            'actor_type' => 'actor_type',
            'created_at' => 'created_at',
        ],
        Schema::RELATIONS => [
            'actor' => [
                Relation::TYPE => Relation::BELONGS_TO_MORPHED,
                Relation::TARGET => Actor::class,
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::OUTER_KEY => ['id'],
                    Relation::INNER_KEY => 'actor_id',
                    Relation::MORPH_KEY => 'actor_type',
                ],
            ],

        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'message' => 'string',
            'actor_id' => 'int',
            'actor_type' => 'string',
            'created_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
    ],
    User::ROLE => [
        Schema::ENTITY => User::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => User::ROLE,
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
            'created_at' => 'created_at',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => ActiveScope::class,
        Schema::TYPECAST => [
            'id' => 'int',
            'name' => 'string',
            'created_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
    ],
    Tenant::ROLE => [
        Schema::ENTITY => Tenant::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => Tenant::ROLE,
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
            'created_at' => 'created_at',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'name' => 'string',
            'created_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
    ],
];
