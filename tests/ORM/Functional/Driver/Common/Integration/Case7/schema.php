<?php

declare(strict_types=1);

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\PostTag;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\Tag;

return [
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
        ],
        Schema::RELATIONS => [
            'postTags' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'postTag',
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::COLLECTION_TYPE => null,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => ['post_id'],
                ],
            ],
        ],
        Schema::TYPECAST => [
            'id' => 'int',
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
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
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
        Schema::RELATIONS => [
            'tag' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'tag',
                Relation::LOAD => Relation::HAS_ONE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => true,
                    Relation::INNER_KEY => [
                        'tag_id',
                    ],
                    Relation::OUTER_KEY => [
                        'id',
                    ],
                ],
            ],
            'post' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'post',
                Relation::LOAD => Relation::HAS_ONE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => [
                        'post_id',
                    ],
                    Relation::OUTER_KEY => [
                        'id',
                    ],
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            'id' => 'int',
            'post_id' => 'int',
            'tag_id' => 'int',
        ],
        Schema::SCHEMA => [],
    ],
];
