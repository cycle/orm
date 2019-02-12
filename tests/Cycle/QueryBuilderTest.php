<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Tests\Fixtures\Comment;
use Spiral\Cycle\Tests\Fixtures\SortByIDConstrain;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Database\Injection\Parameter;

abstract class QueryBuilderTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id_int'        => 'primary',
            'email_str'     => 'string',
            'balance_float' => 'float'
        ]);

        $this->makeTable('comment', [
            'id_int'      => 'primary',
            'user_id_int' => 'integer',
            'message_str' => 'string'
        ]);

        $this->makeFK('comment', 'user_id_int', 'user', 'id_int');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email_str', 'balance_float'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id_int', 'message_str'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
                [2, 'msg 3'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id' => 'id_int', 'email' => 'email_str', 'balance' => 'balance_float'],
                Schema::SCHEMA      => [],
                Schema::TYPECAST    => [
                    'id'      => 'int',
                    'active'  => 'bool',
                    'balance' => 'float'
                ],
                Schema::RELATIONS   => [
                    'comments' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
            ],
            Comment::class => [
                Schema::ROLE        => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id' => 'id_int', 'user_id' => 'user_id_int', 'message' => 'message_str'],
                Schema::TYPECAST    => [
                    'id'      => 'int',
                    'user_id' => 'int'
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
                Schema::CONSTRAIN   => SortByIDConstrain::class
            ]
        ]));
    }

    public function testFetchRelation()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');

        $this->assertEquals([
            [
                'id'       => 1,
                'email'    => 'hello@world.com',
                'balance'  => 100.0,
                'comments' => [
                    [
                        'id'      => 1,
                        'user_id' => 1,
                        'message' => 'msg 1',
                    ],
                    [
                        'id'      => 2,
                        'user_id' => 1,
                        'message' => 'msg 2',
                    ],
                ],
            ],
            [
                'id'       => 2,
                'email'    => 'another@world.com',
                'balance'  => 200.0,
                'comments' => [
                    [
                        'id'      => 3,
                        'user_id' => 2,
                        'message' => 'msg 3',
                    ],
                ],
            ],
        ], $selector->fetchData());
    }

    public function testSimpleWhere()
    {
        $select = new Select($this->orm, User::class);
        list($a) = $select->where('id', 2)->fetchAll();

        $this->assertSame(2, $a->id);
    }

    public function testOrderBy()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testOrderByArray()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->orderBy([
            'id' => 'DESC'
        ])->fetchAll();

        $this->assertSame(2, $a->id);
        $this->assertSame(1, $b->id);
    }

    public function testOrWhere()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->where('id', 2)->orWhere('id', 1)->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testWhereIN()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->where('id', new Parameter([1, 2]))->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testWhereShortSyntax()
    {
        $select = new Select($this->orm, User::class);
        list($a) = $select->where([
            'id' => 2
        ])->orderBy('id')->fetchAll();

        $this->assertSame(2, $a->id);
    }

    public function testWhereShortSyntaxIn()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->where([
            'id' => new Parameter([1, 2])
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testInComplexArray()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->where([
            'id' => ['>' => 0, '<' => 3]
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testInComplexArrayVerify()
    {
        $select = new Select($this->orm, User::class);
        list($a) = $select->where([
            'user.id' => [
                '>'  => 1,
                '<=' => 2
            ]
        ])->orderBy('id')->fetchAll();

        $this->assertSame(2, $a->id);
    }

    public function testRelationOr()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments')
            ->where('comments.message', 'msg 3')
            ->orWhere('comments.message', 'msg 1')
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testRelationComplexParameter()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments')
            ->where([
                'comments.message' => new Parameter(['msg 3', 'msg 1'])
            ])
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testRelationAliased()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments', ['as' => 'c'])
            ->where([
                'c.message' => new Parameter(['msg 3', 'msg 1'])
            ])
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testRelationComplexArray()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments')
            ->where([
                'comments.id' => [
                    '>=' => 1,
                    '<'  => 4
                ]
            ])
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }


    public function testRelationComplexArrayAliased()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments', ['as' => 'cc'])
            ->where([
                'cc.id' => [
                    '>=' => 1,
                    '<'  => 4
                ]
            ])
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testRelationComplexArrayVerify()
    {
        $select = new Select($this->orm, User::class);
        list($a) = $select
            ->with('comments')
            ->where([
                'comments.id' => [
                    '>=' => 3,
                    '<'  => 4
                ]
            ])
            ->orderBy('id')->fetchAll();

        $this->assertSame(2, $a->id);
    }

    public function testWhereClosure()
    {
        $select = new Select($this->orm, User::class);
        list($a) = $select
            ->where(function (Select\QueryBuilder $q) {
                $q->where('id', 2);
            })
            ->orderBy('id')->fetchAll();

        $this->assertSame(2, $a->id);
    }

    public function testWhereClosureRelation()
    {
        $select = new Select($this->orm, User::class);
        list($a) = $select
            ->with('comments')
            ->where(function (Select\QueryBuilder $q) {
                $q->where('comments.message', 'msg 3');
            })
            ->orderBy('id')->fetchAll();

        $this->assertSame(2, $a->id);
    }

    public function testRelationComplexArrayOR()
    {
        $select = new Select($this->orm, User::class);

        list($a, $b) = $select
            ->with('comments')
            ->where([
                '@or' => [
                    ['comments.message' => 'msg 1'],
                    ['comments.message' => 'msg 3'],
                ],
            ])
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testRelationComplexArrayORAliased()
    {
        $select = new Select($this->orm, User::class);

        list($a, $b) = $select
            ->with('comments', ['as' => 'comment_relation'])
            ->where([
                '@or' => [
                    ['comments.message' => 'msg 1'],
                    ['comments.message' => 'msg 3'],
                ],
            ])
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testWhereClosureOr()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->where(function (Select\QueryBuilder $q) {
                $q->where('id', 2);
            })
            ->orWhere(function (Select\QueryBuilder $q) {
                $q->where('id', 1);
            })
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testWhereClosureRelationWithOr()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments')
            ->where(function (Select\QueryBuilder $q) {
                $q->where('comments.message', 'msg 3')->orWhere('id', 1);
            })
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testBetween()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments')
            ->where('comments.id', 'between', 1, 4)
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testBetweenArray()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments')
            ->where(['comments.id' => ['between' => [1, 4]]])
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testWhereNestedClosure()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select
            ->with('comments')
            ->where(function (Select\QueryBuilder $q) {
                $q->where('comments.message', 'msg 3')->orWhere(function (Select\QueryBuilder $q) {
                    $q->where('id', 1);
                });
            })
            ->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testWhereForRelation()
    {
        $select = new Select($this->orm, User::class);
        list($a) = $select->with('comments', [
            'where' => function (Select\QueryBuilder $q) {
                $q->where('message', 'msg 3');
            }
        ])->orderBy('id')->fetchAll();

        $this->assertSame(2, $a->id);
    }

    public function testWhereForRelationVerify()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->with('comments', [
            'where' => function (Select\QueryBuilder $q) {
                $q->where('message', 'msg 3')->orWhere('message', 'msg 1');
            }
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testWhereForRelationArray()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->with('comments', [
            'where' => ['id' => ['in' => new Parameter([1, 3])]]
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);
    }

    public function testLoadRelationsBuilder()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->load('comments', [
            'where' => ['message' => 'msg 1']
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);

        $this->assertCount(1, $a->comments);
        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertCount(0, $b->comments);
    }

    public function testLoadRelationsBuilderMultiple()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->load('comments', [
            'where' => function ($q) {
                $q->where('message', new Parameter(['msg 1', 'msg 3']));
            }
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);

        $this->assertCount(1, $a->comments);
        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertCount(1, $b->comments);
        $this->assertSame('msg 3', $b->comments[0]->message);
    }

    public function testLoadRelationsBuilderInload()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->load('comments', [
            'where'  => ['message' => 'msg 1'],
            'method' => Select\JoinableLoader::INLOAD
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);

        $this->assertCount(1, $a->comments);
        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertCount(0, $b->comments);
    }

    public function testLoadRelationsBuilderInloadVerify()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->load('comments', [
            'where'  => ['message' => 'msg 3'],
            'method' => Select\JoinableLoader::INLOAD
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);

        $this->assertCount(0, $a->comments);
        $this->assertCount(1, $b->comments);
        $this->assertSame('msg 3', $b->comments[0]->message);
    }

    public function testLoadRelationsBuilderMultipleInload()
    {
        $select = new Select($this->orm, User::class);
        list($a, $b) = $select->load('comments', [
            'where'  => function (Select\QueryBuilder $q) {
                $q->where('message', new Parameter(['msg 1', 'msg 3']));
            },
            'method' => Select\JoinableLoader::INLOAD
        ])->orderBy('id')->fetchAll();

        $this->assertSame(1, $a->id);
        $this->assertSame(2, $b->id);


        $this->assertCount(1, $a->comments);
        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertCount(1, $b->comments);
        $this->assertSame('msg 3', $b->comments[0]->message);
    }
}