<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity\Category;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity\Metadata;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity\User;

return [
    'user' => [
        Schema::ENTITY => User::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ],
        Schema::RELATIONS => [],
        Schema::TYPECAST => [
            'id' => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => 2,
        ],
    ],
    'category' => [
        Schema::ENTITY => Category::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'category',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'name' => 'name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ],
        Schema::RELATIONS => [],
        Schema::TYPECAST => [
            'id' => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => 2,
        ],
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
            'content' => 'content',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'post_id' => 'post_id',
            'user_id' => 'user_id',
        ],
        Schema::RELATIONS => [
            'parent' => [
                Relation::TYPE => Relation::REFERS_TO,
                Relation::TARGET => 'comment',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => ['parent_id'],
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
                    Relation::INNER_KEY => ['post_id'],
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => ['user_id'],
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'post_id' => 'int',
            'user_id' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => 2,
        ],
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
            'title' => 'title',
            'content' => 'content',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'best_comment_id' => 'best_comment_id',
            'user_id' => 'user_id',
            'category_id' => 'category_id',
        ],
        Schema::RELATIONS => [
            'best_comment' => [
                Relation::TYPE => Relation::REFERS_TO,
                Relation::TARGET => 'comment',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => ['best_comment_id'],
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'user',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => ['user_id'],
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'category' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'category',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => ['category_id'],
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'metadata' => [
                Relation::TYPE => 1,
                Relation::TARGET => 'post:metadata:metadata',
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [],
            ],

        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'public' => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'best_comment_id' => 'int',
            'user_id' => 'int',
            'category_id' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => 2,
        ],
    ],
    'post:metadata:metadata' => [
        Schema::ENTITY => Metadata::class,
        Schema::SOURCE => Source::class,
        Schema::DATABASE => 'default',
        Schema::MAPPER => Mapper::class,
        Schema::TABLE => 'post',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'data' => 'data',
            'id' => 'id',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'data' => 'string',
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
        Schema::GENERATED_FIELDS => [
            'id' => 2,
        ],
    ],
];
