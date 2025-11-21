<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity\Post;

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
            'content' => 'content',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'post_id' => 'post_id',
        ],
        Schema::RELATIONS => [
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
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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
            'title' => 'title',
            'content' => 'content',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'best_comment_id' => 'best_comment_id',
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
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'public' => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'best_comment_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
];
