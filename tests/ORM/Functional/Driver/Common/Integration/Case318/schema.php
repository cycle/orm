<?php

declare(strict_types=1);

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case318\Entity\Group;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case318\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case318\Entity\UserGroup;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case318\Typecast;

return [
    'user' => [
        Schema::ENTITY => User::class,
        Schema::TABLE => 'users',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::COLUMNS => [
            'uuid' => 'uuid',
            'login' => 'login',
        ],
        Schema::RELATIONS => [
            'groups' => [
                Relation::TYPE => Relation::MANY_TO_MANY,
                Relation::TARGET => Group::class,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'uuid',
                    Relation::OUTER_KEY => 'uuid',
                    Relation::THROUGH_OUTER_KEY => 'group_uuid',
                    Relation::THROUGH_INNER_KEY => 'user_uuid',
                    Relation::THROUGH_ENTITY => UserGroup::class,
                ],
            ],
        ],
        Schema::TYPECAST => [
            'uuid' => 'uuid',
        ],
        Schema::TYPECAST_HANDLER => [
            Typecast::class,
        ],
    ],
    'group' => [
        Schema::ENTITY => Group::class,
        Schema::TABLE => 'groups',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::COLUMNS => [
            'uuid' => 'uuid',
            'title' => 'title',
        ],
        Schema::TYPECAST => [
            'uuid' => 'uuid',
        ],
        Schema::TYPECAST_HANDLER => [
            Typecast::class,
        ],
    ],
    'user_group' => [
        Schema::ENTITY => UserGroup::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user_groups',
        Schema::PRIMARY_KEY => ['user_uuid', 'group_uuid'],
        Schema::COLUMNS => [
            'user_uuid' => 'user_uuid',
            'group_uuid' => 'group_uuid',
        ],
        Schema::TYPECAST => [
            'user_uuid' => 'uuid',
            'group_uuid' => 'uuid',
        ],
        Schema::TYPECAST_HANDLER => [
            Typecast::class,
        ],
    ],
];
