<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case430\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case430\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case430\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case430\Typecast\UuidTypecast;

return [
    'user' => [
        Schema::ENTITY => User::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::FIND_BY_KEYS => ['uuid'],
        Schema::COLUMNS => [
            'uuid' => 'uuid',
            'login' => 'login',
            'passwordHash' => 'password_hash',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ],
        Schema::RELATIONS => [
            'posts' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'post',
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['uuid'],
                    Relation::OUTER_KEY => 'user_uuid',
                ],
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
                    Relation::INNER_KEY => ['uuid'],
                    Relation::OUTER_KEY => 'user_uuid',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'uuid' => 'uuid',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Typecast::class],
    ],
    'post' => [
        Schema::ENTITY => Post::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'post',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::FIND_BY_KEYS => ['uuid'],
        Schema::COLUMNS => [
            'uuid' => 'uuid',
            'slug' => 'slug',
            'title' => 'title',
            'public' => 'public',
            'content' => 'content',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'published_at' => 'published_at',
            'deleted_at' => 'deleted_at',
            'user_uuid' => 'user_uuid',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => User::ROLE,
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'user_uuid',
                    Relation::OUTER_KEY => ['uuid'],
                ],
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
                    Relation::INNER_KEY => ['uuid'],
                    Relation::OUTER_KEY => 'post_uuid',
                ],
            ],
        ],
        Schema::TYPECAST => [
            'uuid' => 'uuid',
            'public' => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
            'user_uuid' => 'uuid',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Typecast::class],
    ],
    'comment' => [
        Schema::ENTITY => Comment::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'comment',
        Schema::PRIMARY_KEY => ['uuid'],
        Schema::FIND_BY_KEYS => ['uuid'],
        Schema::COLUMNS => [
            'uuid' => 'uuid',
            'public' => 'public',
            'content' => 'content',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'published_at' => 'published_at',
            'deleted_at' => 'deleted_at',
            'user_uuid' => 'user_uuid',
            'post_uuid' => 'post_uuid',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => User::ROLE,
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'user_uuid',
                    Relation::OUTER_KEY => ['uuid'],
                ],
            ],
            'post' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'post',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'post_uuid',
                    Relation::OUTER_KEY => ['uuid'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'uuid' => 'uuid',
            'public' => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
            'user_uuid' => 'uuid',
            'post_uuid' => 'uuid',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [UuidTypecast::class, Typecast::class],
    ],
];
