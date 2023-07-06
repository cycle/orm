<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue422\Entity\Billing;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue422\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue422\Entity\SomeEmbedded;

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
            'name' => 'name',
        ],
        Schema::RELATIONS => [
            'billing' => [
                Relation::TYPE => Relation::HAS_ONE,
                Relation::TARGET => 'billing',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'user_id',
                    Relation::ORDER_BY => [],
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'name' => 'string',
        ],
        Schema::SCHEMA => [],
    ],
    'billing' => [
        Schema::ENTITY => Billing::class,
        Schema::MAPPER => Cycle\ORM\Mapper\Mapper::class,
        Schema::SOURCE => Cycle\ORM\Select\Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'billing',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'user_id' => 'user_id',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::REFERS_TO,
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => ['user_id'],
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'someEmbeddable' => [
                Relation::TYPE => Relation::EMBEDDED,
                Relation::TARGET => 'billing:someEmbeddable:someEmbeddable',
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [Cycle\ORM\Parser\Typecast::class],
    ],
    'billing:someEmbeddable:someEmbeddable' =>  [
        Schema::ENTITY => SomeEmbedded::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'billing',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'propertyString' => 'property_string',
            'propertyInt' => 'property_int',
            'id' => 'id',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'propertyString' => 'string',
            'propertyInt' => 'int',
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => null,
    ]
];
