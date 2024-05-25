<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\Entity\Translation;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\Entity\Country;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\Entity\Locale;

return [
    'country' => [
        Schema::ENTITY => Country::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::MAPPER => Mapper::class,
        Schema::TABLE => 'country',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
            'code' => 'code',
            'isFriendly' => 'is_friendly',
        ],
        Schema::RELATIONS => [
            'translations' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'translation',
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'country_id',
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'name' => 'string',
            'code' => 'string',
            'isFriendly' => 'bool',
        ],
        Schema::SCHEMA => [],
    ],
    'translation' => [
        Schema::ENTITY => Translation::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'translation',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'country_id' => 'country_id',
            'locale_id' => 'locale_id',
            'title' => 'title',
        ],
        Schema::RELATIONS => [
            'country' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'country',
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'country_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'locale' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'locale',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'locale_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'country_id' => 'int',
            'locale_id' => 'int',
            'title' => 'string',
        ],
        Schema::SCHEMA => [],
    ],
    'locale' => [
        Schema::ENTITY => Locale::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'locale',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'code' => 'code',
        ],
        Schema::RELATIONS => [
            'translations' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'translation',
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'locale_id',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'code' => 'string',
        ],
        Schema::SCHEMA => [],
    ],
];
