<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate\Entity\PostTag;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate\Entity\Tag;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate\Entity\User;

return [
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
            'id' => 'int',
            'public' => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
            'user_id' => 'int',
            'post_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    'post' => [
        Schema::ENTITY => Post::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::MAPPER => Mapper::class,
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
            'tags' => [
                Relation::TYPE => Relation::MANY_TO_MANY,
                Relation::TARGET => 'tag',
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => ['id'],
                    Relation::THROUGH_ENTITY => 'postTag',
                    Relation::THROUGH_INNER_KEY => 'post_id',
                    Relation::THROUGH_OUTER_KEY => 'tag_id',
                    Relation::THROUGH_WHERE => [],
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
            'id' => 'int',
            'public' => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
            'user_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    'postTag' => [
        Schema::ENTITY => PostTag::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'post_tag',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'post_id' => 'post_id',
            'tag_id' => 'tag_id',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'post_id' => 'int',
            'tag_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    'tag' => [
        Schema::ENTITY => Tag::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'tag',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'label' => 'label',
            'created_at' => 'created_at',
        ],
        Schema::RELATIONS => [
            'posts' => [
                Relation::TYPE => Relation::MANY_TO_MANY,
                Relation::TARGET => 'post',
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => ['id'],
                    Relation::THROUGH_ENTITY => 'postTag',
                    Relation::THROUGH_INNER_KEY => 'tag_id',
                    Relation::THROUGH_OUTER_KEY => 'post_id',
                    Relation::THROUGH_WHERE => [],
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'created_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
    ],
    User::ROLE => [
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
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'user_id',
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
    ],
];
