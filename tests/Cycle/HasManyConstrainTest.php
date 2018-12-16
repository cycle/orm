<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Selector\JoinableLoader;
use Spiral\Cycle\Tests\Fixtures\Comment;
use Spiral\Cycle\Tests\Fixtures\SortedMapper;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;

abstract class HasManyConstrainTest extends BaseTest
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

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('comment', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'level'   => 'integer',
            'message' => 'string'
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'level', 'message'],
            [
                [1, 1, 'msg 1'],
                [1, 2, 'msg 2'],
                [1, 3, 'msg 3'],
                [1, 4, 'msg 4'],
                [2, 1, 'msg 2.1'],
                [2, 2, 'msg 2.2'],
                [2, 3, 'msg 2.3'],
            ]
        );
    }

    public function testOrdered()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'DESC']),
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('msg 4', $a->comments[0]->message);
        $this->assertSame('msg 3', $a->comments[1]->message);
        $this->assertSame('msg 2', $a->comments[2]->message);
        $this->assertSame('msg 1', $a->comments[3]->message);

        $this->assertSame('msg 2.3', $b->comments[0]->message);
        $this->assertSame('msg 2.2', $b->comments[1]->message);
        $this->assertSame('msg 2.1', $b->comments[2]->message);
    }

    public function testOrderedASC()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'ASC']),
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('msg 4', $a->comments[3]->message);
        $this->assertSame('msg 3', $a->comments[2]->message);
        $this->assertSame('msg 2', $a->comments[1]->message);
        $this->assertSame('msg 1', $a->comments[0]->message);

        $this->assertSame('msg 2.3', $b->comments[2]->message);
        $this->assertSame('msg 2.2', $b->comments[1]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
    }

    public function testOrderedASCInload()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'ASC']),
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('msg 4', $a->comments[3]->message);
        $this->assertSame('msg 3', $a->comments[2]->message);
        $this->assertSame('msg 2', $a->comments[1]->message);
        $this->assertSame('msg 1', $a->comments[0]->message);

        $this->assertSame('msg 2.3', $b->comments[2]->message);
        $this->assertSame('msg 2.2', $b->comments[1]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
    }

    public function testOrderedPromisedASC()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'ASC']),
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('msg 4', $a->comments[3]->message);
        $this->assertSame('msg 3', $a->comments[2]->message);
        $this->assertSame('msg 2', $a->comments[1]->message);
        $this->assertSame('msg 1', $a->comments[0]->message);

        $this->assertSame('msg 2.3', $b->comments[2]->message);
        $this->assertSame('msg 2.2', $b->comments[1]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
    }

    public function testOrderedAndWhere()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'ASC']),
            Relation::WHERE     => ['@.level' => ['>=' => 2]]
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments')->fetchAll();

        $this->assertCount(3, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->assertSame('msg 4', $a->comments[2]->message);
        $this->assertSame('msg 3', $a->comments[1]->message);
        $this->assertSame('msg 2', $a->comments[0]->message);
        $this->assertSame('msg 2.3', $b->comments[1]->message);
        $this->assertSame('msg 2.2', $b->comments[0]->message);
    }

    public function testOrderedAndWherePromised()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'ASC']),
            Relation::WHERE     => ['@.level' => ['>=' => 2]]
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->fetchAll();

        $this->assertCount(3, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->assertSame('msg 4', $a->comments[2]->message);
        $this->assertSame('msg 3', $a->comments[1]->message);
        $this->assertSame('msg 2', $a->comments[0]->message);
        $this->assertSame('msg 2.3', $b->comments[1]->message);
        $this->assertSame('msg 2.2', $b->comments[0]->message);
    }

    public function testOrderedAndWhereReversed()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'DESC']),
            Relation::WHERE     => ['@.level' => ['>=' => 2]]
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments')->fetchAll();

        $this->assertCount(3, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->assertSame('msg 4', $a->comments[0]->message);
        $this->assertSame('msg 3', $a->comments[1]->message);
        $this->assertSame('msg 2', $a->comments[2]->message);
        $this->assertSame('msg 2.3', $b->comments[0]->message);
        $this->assertSame('msg 2.2', $b->comments[1]->message);
    }

    public function testOrderedAndWhereReversedInload()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'DESC']),
            Relation::WHERE     => ['@.level' => ['>=' => 2]]
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD
        ])->fetchAll();

        $this->assertCount(3, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->assertSame('msg 4', $a->comments[0]->message);
        $this->assertSame('msg 3', $a->comments[1]->message);
        $this->assertSame('msg 2', $a->comments[2]->message);
        $this->assertSame('msg 2.3', $b->comments[0]->message);
        $this->assertSame('msg 2.2', $b->comments[1]->message);
    }

    public function testOrderedAndWhereReversedPromised()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'DESC']),
            Relation::WHERE     => ['@.level' => ['>=' => 2]]
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->fetchAll();

        $this->assertCount(3, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->assertSame('msg 4', $a->comments[0]->message);
        $this->assertSame('msg 3', $a->comments[1]->message);
        $this->assertSame('msg 2', $a->comments[2]->message);
        $this->assertSame('msg 2.3', $b->comments[0]->message);
        $this->assertSame('msg 2.2', $b->comments[1]->message);
    }

    public function testCustomWhere()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'ASC']),
            Relation::WHERE     => ['@.level' => ['>=' => 2]]
        ]);

        // overwrites default one
        list($a, $b) = (new Selector($this->orm, User::class))->orderBy('user.id')->load('comments', [
            'where' => ['@.level' => 1]
        ])->fetchAll();

        $this->assertCount(1, $a->comments);
        $this->assertCount(1, $b->comments);

        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
    }

    public function testCustomWhereInload()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'ASC']),
            Relation::WHERE     => ['@.level' => ['>=' => 2]]
        ]);

        // overwrites default one
        list($a, $b) = (new Selector($this->orm, User::class))->orderBy('user.id')->load('comments', [
            'where'  => ['@.level' => 1],
            'method' => JoinableLoader::INLOAD
        ])->fetchAll();

        $this->assertCount(1, $a->comments);
        $this->assertCount(1, $b->comments);

        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
    }

    public function testWithWhere()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE => ['@.level' => 4]
        ]);

        // second user has been filtered out
        $res = (new Selector($this->orm, User::class))->with('comments')->fetchAll();

        $this->assertCount(1, $res);
        $this->assertSame('hello@world.com', $res[0]->email);
    }

    public function testWithWhereAltered()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE => ['@.level' => 4]
        ]);

        // second user has been filtered out
        $res = (new Selector($this->orm, User::class))->with('comments', [
            'where' => ['@.level' => 1]
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(2, $res);
        $this->assertSame('hello@world.com', $res[0]->email);
        $this->assertSame('another@world.com', $res[1]->email);
    }

    public function testLimitParentSelection()
    {
        $this->orm = $this->withCommentsSchema([
        ]);

        // second user has been filtered out
        $res = (new Selector($this->orm, User::class))
            ->load('comments')
            ->limit(1)->orderBy('user.id')->fetchAll();

        $this->assertCount(1, $res);
        $this->assertSame('hello@world.com', $res[0]->email);
        $this->assertCount(4, $res[0]->comments);
    }

    /**
     * @expectedException \Spiral\Cycle\Exception\LoaderException
     */
    public function testLimitParentSelectionError()
    {
        $this->orm = $this->withCommentsSchema([]);

        // do not allow limits with joined and loaded relations
        (new Selector($this->orm, User::class))
            ->load('comments', ['method' => JoinableLoader::INLOAD])
            ->limit(1)->orderBy('user.id')->fetchAll();
    }

    public function testInloadWithOrderAndWhere()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE     => ['@.level' => ['>=' => 3]],
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.level' => 'DESC']),
        ]);

        // sort by users and then by comments and only include comments with level > 3
        $res = (new Selector($this->orm, User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD
        ])->orderBy('user.id', 'DESC')->fetchAll();

        $this->assertCount(2, $res);
        $this->assertSame('hello@world.com', $res[1]->email);
        $this->assertSame('another@world.com', $res[0]->email);

        $this->assertCount(2, $res[1]->comments);
        $this->assertCount(1, $res[0]->comments);

        $this->assertSame('msg 4', $res[1]->comments[0]->message);
        $this->assertSame('msg 3', $res[1]->comments[1]->message);
        $this->assertSame('msg 2.3', $res[0]->comments[0]->message);
    }

    /**
     * @expectedException \Spiral\Database\Exception\StatementException
     */
    public function testInvalidOrderBy()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE     => ['@.level' => ['>=' => 3]],
            Relation::CONSTRAIN => new Selector\QueryConstrain([], ['@.column' => 'DESC']),
        ]);

        // sort by users and then by comments and only include comments with level > 3
        (new Selector($this->orm, User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD,
        ])->orderBy('user.id', 'DESC')->fetchAll();
    }

    protected function withCommentsSchema(array $relationSchema)
    {
        return $this->orm->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => SortedMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'comments' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                                Relation::CASCADE   => true,
                                Relation::INNER_KEY => 'id',
                                Relation::OUTER_KEY => 'user_id',
                            ] + $relationSchema,
                    ]
                ]
            ],
            Comment::class => [
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'level', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }
}