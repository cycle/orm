<?php

declare(strict_types=1);

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity\Account;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity\Identity;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity\Profile;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity\UserName;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Typecast\UuidTypecast;

return [
    'account' => [
        Schema::ENTITY => Account::class,
        Schema::MAPPER => Cycle\ORM\Mapper\Mapper::class,
        Schema::SOURCE => Cycle\ORM\Select\Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'account',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::FIND_BY_KEYS => ['uuid'],
        Schema::COLUMNS => [
            'uuid' => 'uuid',
            'email' => 'email',
            'passwordHash' => 'password_hash',
            'updatedAt' => 'updated_at',
        ],
        Schema::RELATIONS => [
            'identity' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'identity',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => ['uuid'],
                    Relation::OUTER_KEY => ['uuid'],
                    Relation::INVERSION => 'account',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'uuid' => 'uuid',
            'updatedAt' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Cycle\ORM\Parser\Typecast::class],
    ],
    'identity' => [
        Schema::ENTITY => Identity::class,
        Schema::MAPPER => Cycle\ORM\Mapper\Mapper::class,
        Schema::SOURCE => Cycle\ORM\Select\Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'identity',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::FIND_BY_KEYS => ['uuid'],
        Schema::COLUMNS => [
            'uuid' => 'uuid',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
            'deletedAt' => 'deleted_at',
        ],
        Schema::RELATIONS => [
            'profile' => [
                Relation::TYPE => Relation::HAS_ONE,
                Relation::TARGET => 'profile',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => ['uuid'],
                    Relation::OUTER_KEY => ['uuid'],
                    Relation::INVERSION => 'identity',
                ],
            ],
            'account' => [
                Relation::TYPE => Relation::HAS_ONE,
                Relation::TARGET => 'account',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => ['uuid'],
                    Relation::OUTER_KEY => ['uuid'],
                    Relation::INVERSION => 'identity',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'uuid' => 'uuid',
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
            'deletedAt' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Cycle\ORM\Parser\Typecast::class],
    ],
    'profile' => [
        Schema::ENTITY => Profile::class,
        Schema::MAPPER => Cycle\ORM\Mapper\Mapper::class,
        Schema::SOURCE => Cycle\ORM\Select\Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'profile',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::FIND_BY_KEYS => ['uuid'],
        Schema::COLUMNS => [
            'uuid' => 'uuid',
            'updatedAt' => 'updated_at',
        ],
        Schema::RELATIONS => [
            'name' => [
                Relation::TYPE => 1,
                Relation::TARGET => 'profile:userName:name',
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [],
            ],
            'identity' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'identity',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => ['uuid'],
                    Relation::OUTER_KEY => ['uuid'],
                    Relation::INVERSION => 'profile',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'uuid' => 'uuid',
            'updatedAt' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Cycle\ORM\Parser\Typecast::class],
    ],
    'profile:userName:name' => [
        Schema::ENTITY => UserName::class,
        Schema::MAPPER => Cycle\ORM\Mapper\Mapper::class,
        Schema::SOURCE => Cycle\ORM\Select\Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'profile',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::FIND_BY_KEYS => ['uuid'],
        Schema::COLUMNS => [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'uuid' => 'uuid',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'uuid' => 'uuid',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Cycle\ORM\Parser\Typecast::class],
    ],
];
