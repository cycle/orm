<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case575\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case575\Entity\User;

return [
    User::ROLE => [
        Schema::ENTITY => User::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'user',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'login' => 'login',
        ],
        Schema::RELATIONS => [
            'comments' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => Comment::ROLE,
                Relation::COLLECTION_TYPE => 'array',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => ['id' => 'ASC'],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'user_id',
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
    Comment::ROLE => [
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
            'content' => 'content',
            'user_id' => 'user_id',
        ],
        Schema::RELATIONS => [
            'user' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => User::ROLE,
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => 'user_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
            'user_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
];
