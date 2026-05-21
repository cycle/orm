<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity\Order;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity\OrderShipping;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity\UserCredentials;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity\UserProfile;

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
            'email' => 'email',
            'balance' => 'balance',
        ],
        Schema::RELATIONS => [
            'credentials' => [
                Relation::TYPE => Relation::EMBEDDED,
                Relation::TARGET => 'user:credentials',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [],
            ],
            'profile' => [
                Relation::TYPE => Relation::EMBEDDED,
                Relation::TARGET => 'user:profile',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [],
            ],
            'comments' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'comment',
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'user_id',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'balance' => 'float',
        ],
        Schema::SCHEMA => [],
    ],
    'user:credentials' => [
        Schema::ENTITY => UserCredentials::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'username' => 'creds_username',
            'password' => 'creds_password',
            'numLogins' => 'creds_num_logins',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'numLogins' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    'user:profile' => [
        Schema::ENTITY => UserProfile::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'bio' => 'profile_bio',
            'image' => 'profile_image',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    'comment' => [
        Schema::ENTITY => Comment::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'comment',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'user_id' => 'user_id',
            'message' => 'message',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'user_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    'order' => [
        Schema::ENTITY => Order::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'order_table',
        Schema::PRIMARY_KEY => ['tenant_id', 'number'],
        Schema::FIND_BY_KEYS => ['tenant_id', 'number'],
        Schema::COLUMNS => [
            'tenant_id' => 'tenant_id',
            'number' => 'number',
            'total' => 'total',
        ],
        Schema::RELATIONS => [
            'shipping' => [
                Relation::TYPE => Relation::EMBEDDED,
                Relation::TARGET => 'order:shipping',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'tenant_id' => 'int',
            'number' => 'int',
            'total' => 'float',
        ],
        Schema::SCHEMA => [],
    ],
    'order:shipping' => [
        Schema::ENTITY => OrderShipping::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'order_table',
        Schema::PRIMARY_KEY => ['tenant_id', 'number'],
        Schema::FIND_BY_KEYS => ['tenant_id', 'number'],
        Schema::COLUMNS => [
            'tenant_id' => 'tenant_id',
            'number' => 'number',
            'address' => 'ship_address',
            'recipient' => 'ship_recipient',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'tenant_id' => 'int',
            'number' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
];
