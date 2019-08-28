<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\SortByIDConstrain;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class HasManyNestedConditionTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);


        $this->makeTable('post', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'title'   => 'string'
        ]);

        $this->makeTable('comment', [
            'id'      => 'primary',
            'user_id' => 'integer,null',
            'post_id' => 'integer',
            'message' => 'string'
        ]);

        $this->makeFK('post', 'user_id', 'user', 'id');
        $this->makeFK('comment', 'user_id', 'user', 'id');
        $this->makeFK('comment', 'post_id', 'post', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('post')->insertMultiple(
            ['user_id', 'title'],
            [
                [1, 'post 1'],
                [1, 'post 2'],
                [1, 'post 3'],
            ]
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'post_id', 'message'],
            [
                [1, 1, 'msg 1'],
                [1, 1, 'msg 2'],
                [1, 2, 'msg 3'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'posts' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Post::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
            ],
            Post::class    => [
                Schema::ROLE        => 'post',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'title'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'comments' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'post_id',
                        ],
                    ]
                ],
                Schema::CONSTRAIN   => SortByIDConstrain::class
            ],
            Comment::class => [
                Schema::ROLE        => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'post_id', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
                Schema::CONSTRAIN   => SortByIDConstrain::class
            ]
        ]));
    }

    public function testFetchRelation()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('posts.comments')->orderBy('user.id');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'posts'   => [
                    [
                        'id'       => 1,
                        'user_id'  => 1,
                        'title'    => 'post 1',
                        'comments' => [
                            [
                                'id'      => 1,
                                'user_id' => 1,
                                'post_id' => 1,
                                'message' => 'msg 1',
                            ],
                            [
                                'id'      => 2,
                                'user_id' => 1,
                                'post_id' => 1,
                                'message' => 'msg 2',
                            ],
                        ],
                    ],
                    [
                        'id'       => 2,
                        'user_id'  => 1,
                        'title'    => 'post 2',
                        'comments' => [
                            [
                                'id'      => 3,
                                'user_id' => 1,
                                'post_id' => 2,
                                'message' => 'msg 3',
                            ],
                        ],
                    ],
                    [
                        'id'       => 3,
                        'user_id'  => 1,
                        'title'    => 'post 3',
                        'comments' =>
                            [
                            ],
                    ],
                ],
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'posts'   =>
                    [
                    ],
            ],
        ], $selector->fetchData());
    }

    // only load posts with comments
    public function testFetchFiltered()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('posts', [
            'where' => function (Select\QueryBuilder $qb) {
                $qb->distinct()->where('comments.id', '!=', null);
            }
        ])->orderBy('user.id');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'posts'   => [
                    [
                        'id'      => 1,
                        'user_id' => 1,
                        'title'   => 'post 1'
                    ],
                    [
                        'id'      => 2,
                        'user_id' => 1,
                        'title'   => 'post 2',
                    ],
                ],
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'posts'   => [],
            ],
        ], $selector->fetchData());
    }
}