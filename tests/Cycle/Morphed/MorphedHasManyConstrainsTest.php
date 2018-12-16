<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Morphed;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Tests\BaseTest;
use Spiral\Cycle\Tests\Fixtures\Comment;
use Spiral\Cycle\Tests\Fixtures\Post;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;

abstract class MorphedHasManyConstrainsTest extends BaseTest
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

        $this->makeTable('post', [
            'id'      => 'primary',
            'user_id' => 'integer,nullable',
            'title'   => 'string',
            'content' => 'string'
        ]);

        $this->getDatabase()->table('post')->insertMultiple(
            ['title', 'user_id', 'content'],
            [
                ['post 1', 1, 'post 1 body'],
                ['post 2', 1, 'post 2 body'],
                ['post 3', 2, 'post 3 body'],
                ['post 4', 2, 'post 4 body'],
            ]
        );

        $this->makeTable('comment', [
            'id'          => 'primary',
            'parent_id'   => 'integer',
            'parent_type' => 'string',
            'level'       => 'int',
            'message'     => 'string'
        ]);

        $this->getDatabase()->table('comment')->insertMultiple(
            ['parent_id', 'parent_type', 'level', 'message',],
            [
                [1, 'user', 1, 'msg 1'],
                [1, 'user', 2, 'msg 2'],
                [1, 'user', 3, 'msg 3'],
                [1, 'user', 4, 'msg 4'],
                [2, 'user', 1, 'msg 2.1'],
                [2, 'user', 2, 'msg 2.2'],
                [2, 'user', 3, 'msg 2.3'],
                [1, 'post', 1, 'p.msg 1'],
                [1, 'post', 2, 'p.msg 2'],
                [1, 'post', 3, 'p.msg 3'],
                [1, 'post', 4, 'p.msg 4'],
                [2, 'post', 1, 'p.msg 2.1'],
                [2, 'post', 2, 'p.msg 2.2'],
                [2, 'post', 3, 'p.msg 2.3'],
            ]
        );
    }

    public function testOrdered()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'DESC']),
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
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
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

    public function testOrderedPosts()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'DESC']),
        ]);

        list($a, $b) = (new Selector($this->orm, Post::class))->load('comments')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('p.msg 4', $a->comments[0]->message);
        $this->assertSame('p.msg 3', $a->comments[1]->message);
        $this->assertSame('p.msg 2', $a->comments[2]->message);
        $this->assertSame('p.msg 1', $a->comments[3]->message);

        $this->assertSame('p.msg 2.3', $b->comments[0]->message);
        $this->assertSame('p.msg 2.2', $b->comments[1]->message);
        $this->assertSame('p.msg 2.1', $b->comments[2]->message);
    }

    public function testOrderedPostsASC()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
        ]);

        list($a, $b) = (new Selector($this->orm, Post::class))->load('comments')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('p.msg 4', $a->comments[3]->message);
        $this->assertSame('p.msg 3', $a->comments[2]->message);
        $this->assertSame('p.msg 2', $a->comments[1]->message);
        $this->assertSame('p.msg 1', $a->comments[0]->message);

        $this->assertSame('p.msg 2.3', $b->comments[2]->message);
        $this->assertSame('p.msg 2.2', $b->comments[1]->message);
        $this->assertSame('p.msg 2.1', $b->comments[0]->message);
    }

    public function testOrderedInload()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'DESC']),
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments', [
            'method' => Selector\JoinableLoader::INLOAD
        ])->fetchAll();

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

    public function testOrderedASCInload()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments', [
            'method' => Selector\JoinableLoader::INLOAD
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

    public function testOrderedPostsInload()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'DESC']),
        ]);

        list($a, $b) = (new Selector($this->orm, Post::class))->load('comments', [
            'method' => Selector\JoinableLoader::INLOAD
        ])->orderBy('post.id', 'ASC')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('p.msg 4', $a->comments[0]->message);
        $this->assertSame('p.msg 3', $a->comments[1]->message);
        $this->assertSame('p.msg 2', $a->comments[2]->message);
        $this->assertSame('p.msg 1', $a->comments[3]->message);

        $this->assertSame('p.msg 2.3', $b->comments[0]->message);
        $this->assertSame('p.msg 2.2', $b->comments[1]->message);
        $this->assertSame('p.msg 2.1', $b->comments[2]->message);
    }

    public function testOrderedPostsASCInload()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
        ]);

        list($a, $b) = (new Selector($this->orm, Post::class))->load('comments', [
            'method' => Selector\JoinableLoader::INLOAD
        ])->orderBy('post.id', 'ASC')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('p.msg 4', $a->comments[3]->message);
        $this->assertSame('p.msg 3', $a->comments[2]->message);
        $this->assertSame('p.msg 2', $a->comments[1]->message);
        $this->assertSame('p.msg 1', $a->comments[0]->message);

        $this->assertSame('p.msg 2.3', $b->comments[2]->message);
        $this->assertSame('p.msg 2.2', $b->comments[1]->message);
        $this->assertSame('p.msg 2.1', $b->comments[0]->message);
    }

    public function testOrderedPromisedASC()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
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
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
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
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
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

    public function testPostOrderedPromisedASC()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
        ]);

        list($a, $b) = (new Selector($this->orm, Post::class))->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('p.msg 4', $a->comments[3]->message);
        $this->assertSame('p.msg 3', $a->comments[2]->message);
        $this->assertSame('p.msg 2', $a->comments[1]->message);
        $this->assertSame('p.msg 1', $a->comments[0]->message);

        $this->assertSame('p.msg 2.3', $b->comments[2]->message);
        $this->assertSame('p.msg 2.2', $b->comments[1]->message);
        $this->assertSame('p.msg 2.1', $b->comments[0]->message);
    }

    public function testPostOrderedAndWhere()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
        ]);

        list($a, $b) = (new Selector($this->orm, Post::class))->load('comments')->fetchAll();

        $this->assertCount(3, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->assertSame('p.msg 4', $a->comments[2]->message);
        $this->assertSame('p.msg 3', $a->comments[1]->message);
        $this->assertSame('p.msg 2', $a->comments[0]->message);
        $this->assertSame('p.msg 2.3', $b->comments[1]->message);
        $this->assertSame('p.msg 2.2', $b->comments[0]->message);
    }

    public function testPostOrderedAndWherePromised()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
        ]);

        list($a, $b) = (new Selector($this->orm, Post::class))->fetchAll();

        $this->captureReadQueries();
        $this->assertCount(3, $a->comments);
        $this->assertCount(2, $b->comments);
        $this->assertNumReads(2);

        $this->assertSame('p.msg 4', $a->comments[2]->message);
        $this->assertSame('p.msg 3', $a->comments[1]->message);
        $this->assertSame('p.msg 2', $a->comments[0]->message);
        $this->assertSame('p.msg 2.3', $b->comments[1]->message);
        $this->assertSame('p.msg 2.2', $b->comments[0]->message);
    }

    public function testOrderedAndWhereReversed()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'DESC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
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
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'DESC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
        ]);

        list($a, $b) = (new Selector($this->orm, User::class))->load('comments', [
            'method' => Selector\JoinableLoader::INLOAD
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
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'DESC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
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
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
        ]);

        // overwrites default one
        list($a, $b) = (new Selector($this->orm, User::class))->load('comments', [
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
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]]
        ]);

        // overwrites default one
        list($a, $b) = (new Selector($this->orm, User::class))->load('comments', [
            'where'  => ['@.level' => 1],
            'method' => Selector\JoinableLoader::INLOAD
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
            ->load('comments', ['method' => Selector\JoinableLoader::INLOAD])
            ->limit(1)->orderBy('user.id')->fetchAll();
    }

    public function testInloadWithOrderAndWhere()
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE => ['@.level' => ['>=' => 3]],
            Relation::SCOPE => new Selector\QueryScope([], ['@.level' => 'DESC']),
        ]);

        // sort by users and then by comments and only include comments with level > 3
        $res = (new Selector($this->orm, User::class))->load('comments', [
            'method' => Selector\JoinableLoader::INLOAD
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
            Relation::WHERE => ['@.level' => ['>=' => 3]],
            Relation::SCOPE => new Selector\QueryScope([], ['@.column' => 'DESC']),
        ]);

        // sort by users and then by comments and only include comments with level > 3
        (new Selector($this->orm, User::class))->load('comments', [
            'method' => Selector\JoinableLoader::INLOAD,
        ])->orderBy('user.id', 'DESC')->fetchAll();
    }

    protected function withCommentsSchema(array $relationSchema)
    {
        return $this->orm->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'comments' => [
                        Relation::TYPE   => Relation::MORPHED_HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                                Relation::CASCADE   => true,
                                Relation::INNER_KEY => 'id',
                                Relation::OUTER_KEY => 'parent_id',
                                Relation::MORPH_KEY => 'parent_type',
                            ] + $relationSchema,
                    ]
                ]
            ],
            Post::class    => [
                Schema::ALIAS       => 'post',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'title', 'content'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'comments' => [
                        Relation::TYPE   => Relation::MORPHED_HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                                Relation::CASCADE   => true,
                                Relation::INNER_KEY => 'id',
                                Relation::OUTER_KEY => 'parent_id',
                                Relation::MORPH_KEY => 'parent_type',
                            ] + $relationSchema,
                    ],
                ]
            ],
            Comment::class => [
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'parent_id', 'parent_type', 'message', 'level'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }
}