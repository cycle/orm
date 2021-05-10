<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * This tests provides ability to create deep linked trees using pivoted entity.
 * We did not plan to have such functionality
 * but the side effect of having pivot loader to behave as normal made it possible.
 */
abstract class ManyToManyLoadingTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->makeTable('tag', [
            'id'   => 'primary',
            'name' => 'string'
        ]);

        $this->makeTable('tag_user_map', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'tag_id'  => 'integer',
            'as'      => 'string,nullable'
        ]);

        $this->makeTable('images', [
            'id'        => 'primary',
            'parent_id' => 'integer',
            'url'       => 'string'
        ]);

        $this->makeFK('tag_user_map', 'user_id', 'user', 'id');
        $this->makeFK('tag_user_map', 'tag_id', 'tag', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('tag')->insertMultiple(
            ['name'],
            [
                ['tag a'],
                ['tag b'],
                ['tag c'],
            ]
        );

        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id', 'as'],
            [
                [1, 1, 'primary'],
                [1, 2, 'secondary'],
                [2, 3, 'primary'],
            ]
        );

        $this->getDatabase()->table('images')->insertMultiple(
            ['parent_id', 'url'],
            [
                [1, 'first.jpg'],
                [3, 'second.png'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class       => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::TYPECAST    => ['id' => 'int', 'balance' => 'float'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'tags' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE           => true,
                            Relation::THROUGH_ENTITY    => TagContext::class,
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'tag_id',
                        ],
                    ]
                ]
            ],
            Tag::class        => [
                Schema::ROLE        => 'tag',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name'],
                Schema::TYPECAST    => ['id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
            TagContext::class => [
                Schema::ROLE        => 'tag_context',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'tag_id', 'as'],
                Schema::TYPECAST    => ['id' => 'int', 'user_id' => 'int', 'tag_id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'image' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Image::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                        ],
                    ]
                ]
            ],
            Image::class      => [
                Schema::ROLE        => 'image',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'images',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'url', 'parent_id'],
                Schema::TYPECAST    => ['id' => 'int', 'parent_id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testLoadSortedByPivot(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('tags', [
            'load' => function (Select\QueryBuilder $q): void {
                $q->orderBy('@.@.as', 'DESC');
            }
        ])->orderBy('id', 'ASC');

        $this->assertSame([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'tags'    => [
                    [
                        'id'      => 2,
                        'user_id' => 1,
                        'tag_id'  => 2,
                        'as'      => 'secondary',
                        '@'       => [
                            'id'   => 2,
                            'name' => 'tag b',
                        ],
                    ],
                    [
                        'id'      => 1,
                        'user_id' => 1,
                        'tag_id'  => 1,
                        'as'      => 'primary',
                        '@'       => [
                            'id'   => 1,
                            'name' => 'tag a',
                        ],
                    ],
                ],
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'tags'    => [
                    [
                        'id'      => 3,
                        'user_id' => 2,
                        'tag_id'  => 3,
                        'as'      => 'primary',
                        '@'       => [
                            'id'   => 3,
                            'name' => 'tag c',
                        ],
                    ],
                ],
            ],
        ], $selector->fetchData());
    }

    public function testLoadSortedByPivotInload(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('tags', [
            'method' => Select::SINGLE_QUERY,
            'load'   => function (Select\QueryBuilder $q): void {
                $q->orderBy('@.@.as', 'DESC');
            }
        ])->orderBy('id', 'ASC');

        $this->assertSame([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'tags'    => [
                    [
                        'id'      => 2,
                        'user_id' => 1,
                        'tag_id'  => 2,
                        'as'      => 'secondary',
                        '@'       => [
                            'id'   => 2,
                            'name' => 'tag b',
                        ],
                    ],
                    [
                        'id'      => 1,
                        'user_id' => 1,
                        'tag_id'  => 1,
                        'as'      => 'primary',
                        '@'       => [
                            'id'   => 1,
                            'name' => 'tag a',
                        ],
                    ],
                ],
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'tags'    => [
                    [
                        'id'      => 3,
                        'user_id' => 2,
                        'tag_id'  => 3,
                        'as'      => 'primary',
                        '@'       => [
                            'id'   => 3,
                            'name' => 'tag c',
                        ],
                    ],
                ],
            ],
        ], $selector->fetchData());
    }
}
