<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511\Entity\City;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511\Entity\Passport;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511\Entity\VisitPermission;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511\Entity\VisitPermissionCityPivot;

return [
    'user' => [
        Schema::ENTITY => User::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        5 => 'default',
        6 => 'user',
        7 => ['id'],
        8 => ['id'],
        9 => [
            'id' => 'id',
            'login' => 'login',
        ],
        10 => [
            'visitPermission' => [
                0 => 10,
                1 => 'visitPermission',
                3 => 10,
                2 =>
                    [
                        30 => true,
                        31 => false,
                        33 => ['id'],
                        32 => ['user_id'],
                    ],
            ],
            'passport' => [
                0 => 10,
                1 => 'passport',
                3 => 10,
                2 =>
                    [
                        30 => true,
                        31 => true,
                        33 => ['id'],
                        32 => ['user_id'],
                    ],
            ],
        ],
        12 => null,
        13 => [
            'id' => 'int',
        ],
        14 => [],
        19 => null,
        20 => [
            'id' => 2,
        ],
    ],
    'passport' => [
        Schema::ENTITY => Passport::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        5 => 'default',
        6 => 'passport',
        7 => ['id'],
        8 => ['id'],
        9 => [
            'id' => 'id',
            'number' => 'number',
            'user_id' => 'user_id',
        ],
        10 => [
            'user' => [
                0 => 12,
                1 => 'user',
                3 => 10,
                2 => [
                    30 => true,
                    31 => true,
                    33 => ['user_id'],
                    32 => ['id'],
                ],
            ],
        ],
        12 => null,
        13 => [
            'id' => 'int',
            'number' => 'string',
            'user_id' => 'int',
        ],
        14 => [],
        19 => null,
        20 => [
            'id' => 2,
        ],
    ],
    'city' => [
        Schema::ENTITY => City::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        5 => 'default',
        6 => 'city',
        7 => ['id'],
        8 => ['id'],
        9 => [
            'id' => 'id',
            'name' => 'name',
        ],
        10 => [],
        12 => null,
        13 => [
            'id' => 'int',
            'name' => 'string',
        ],
        14 => [],
        19 => null,
        20 => [
            'id' => 2,
        ],
    ],
    'visitPermissionCityPivot' => [
        Schema::ENTITY => VisitPermissionCityPivot::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        5 => 'default',
        6 => 'user_visit__permission_city',
        7 => ['id'],
        8 => ['id'],
        9 => [
            'id' => 'id',
            'user_id' => 'user_id',
            'city_id' => 'city_id',
        ],
        10 => [],
        12 => null,
        13 => [
            'id' => 'int',
            'user_id' => 'int',
            'city_id' => 'int',
        ],
        14 => [
        ],
        19 => null,
        20 => [
            'id' => 2,
        ],
    ],
    'visitPermission' => [
        Schema::ENTITY => VisitPermission::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        5 => 'default',
        6 => 'user_visit_permission',
        7 => ['user_id'],
        8 => ['user_id'],
        9 => [
            'user_id' => 'user_id',
            'allCities' => 'all_cities',
        ],
        10 => [
            'user' => [
                0 => 12,
                1 => 'user',
                3 => 10,
                2 =>
                    [
                        30 => true,
                        31 => false,
                        33 => ['user_id'],
                        32 => ['id'],
                    ],
            ],
            'cities' => [
                0 => 14,
                1 => 'city',
                3 => 10,
                2 => [
                    30 => true,
                    31 => false,
                    41 => [],
                    42 => [],
                    33 => ['user_id'],
                    32 => ['id'],
                    52 => 'visitPermissionCityPivot',
                    50 => ['user_id'],
                    51 => ['city_id'],
                    54 => [],
                    4 => null,
                ],
            ],
        ],
        12 => null,
        13 => [
            'user_id' => 'int',
            'allCities' => 'bool',
        ],
        14 => [],
        19 => null,
        20 => [
            'user_id' => 2,
        ],
    ],
];
