<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue380\Entity\User;

return [
    'user' => [
        Schema::ENTITY => User::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'username' => 'username',
            'age' => 'age',
        ],
        Schema::RELATIONS => [
            'aliases' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'alias',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => ['user_id'],
                ],
            ],
            'emails' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'email',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'id',
                    Relation::OUTER_KEY => ['user_id'],
                ],
            ],
            'phones' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'phone',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'id',
                    Relation::OUTER_KEY => ['user_id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'age' => 'int',
        ],
        Schema::SCHEMA => [],
    ],

    'alias' => [
        Schema::ENTITY => User\Alias::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::MAPPER => Mapper::class,
        Schema::TABLE => 'user_alias',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'value' => 'value',
            'user_id' => 'user_id',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'user_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'value' => 'string',
            'user_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],

    'email' => [
        Schema::ENTITY => User\Email::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user_email',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'value' => 'value',
            'user_id' => 'user_id',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'user_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'value' => 'string',
            'user_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],

    'phone' => [
        Schema::ENTITY => User\Phone::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user_phone',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'value' => 'value',
            'user_id' => 'user_id',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'user_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'value' => 'string',
            'user_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],

];
