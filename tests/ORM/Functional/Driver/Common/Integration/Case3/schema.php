<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case3\Entity\Currency;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case3\Entity\User;

return [
    'currency' => [
        Schema::ENTITY => Currency::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'currencies',
        Schema::PRIMARY_KEY => ['code'],
        Schema::FIND_BY_KEYS => ['code'],
        Schema::COLUMNS => [
            'code' => 'code',
            'name' => 'name',
        ],
    ],
    'user' => [
        Schema::ENTITY => User::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::MAPPER => Mapper::class,
        Schema::TABLE => 'users',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
            'currency_code' => 'currency_code',
        ],
        Schema::RELATIONS => [
            'currency' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'currency',
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [
                    Relation::CASCADE => false,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'currency_code',
                    Relation::OUTER_KEY => ['code'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
        ],
    ],
];
