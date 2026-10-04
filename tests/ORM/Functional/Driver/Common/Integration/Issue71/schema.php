<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71\Entity\PostTag;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71\Entity\Tag;

return [
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
            'name' => 'name',
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
                    Relation::THROUGH_ENTITY => 'post_tag',
                    Relation::THROUGH_INNER_KEY => ['tag_id'],
                    Relation::THROUGH_OUTER_KEY => ['post_id'],
                    Relation::THROUGH_WHERE => [],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'name' => 'string',
        ],
        Schema::SCHEMA => [],
    ],
    'post' => [
        Schema::ENTITY => Post::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'post',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'title' => 'title',
        ],
        Schema::RELATIONS => [
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
                    Relation::THROUGH_ENTITY => 'post_tag',
                    Relation::THROUGH_INNER_KEY => ['post_id'],
                    Relation::THROUGH_OUTER_KEY => ['tag_id'],
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
                    Relation::NULLABLE => true,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => ['id' => 'ASC'],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => ['post_id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'title' => 'string',
        ],
        Schema::SCHEMA => [],
    ],
    'post_tag' => [
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
        Schema::TYPECAST => [
            'id' => 'int',
            'post_id' => 'int',
            'tag_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    'comment' => [
        Schema::ENTITY => Comment::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'comment',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'post_id' => 'post_id',
            'message' => 'message',
        ],
        Schema::RELATIONS => [],
        Schema::TYPECAST => [
            'id' => 'int',
            'post_id' => 'int',
            'message' => 'string',
        ],
        Schema::SCHEMA => [],
    ],
];
