<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431\Typecast\OneWayUuidTypecast;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431\Typecast\OneWayValueInterfaceTypecast;

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
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'user_id',
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
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'user_id',
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'uuid',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [OneWayUuidTypecast::class, Typecast::class],
    ],
    'post' => [
        Schema::ENTITY => Post::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'post',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'slug' => 'slug',
            'title' => 'title',
            'public' => 'public',
            'content' => 'content',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'published_at' => 'published_at',
            'deleted_at' => 'deleted_at',
            'user_id' => 'user_id',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => User::ROLE,
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'user_id',
                    Relation::OUTER_KEY => ['id'],
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
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'post_id',
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'uuid',
            'public' => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
            'user_id' => 'uuid',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [OneWayValueInterfaceTypecast::class, Typecast::class],
    ],
    'comment' => [
        Schema::ENTITY => Comment::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'comment',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'public' => 'public',
            'content' => 'content',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'published_at' => 'published_at',
            'deleted_at' => 'deleted_at',
            'user_id' => 'user_id',
            'post_id' => 'post_id',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => User::ROLE,
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'user_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'post' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'post',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'post_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'uuid',
            'public' => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
            'user_id' => 'uuid',
            'post_id' => 'uuid',
        ],
        Schema::SCHEMA => [],
        Schema::TYPECAST_HANDLER => [OneWayUuidTypecast::class, Typecast::class],
    ],
];
