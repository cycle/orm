<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Collection\PromisedCollection;
use Spiral\ORM\Entity\Mapper;
use Spiral\ORM\Heap;
use Spiral\ORM\Loader\RelationLoader;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\Comment;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class HasManyPromiseTest extends BaseTest
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
            'message' => 'string'
        ]);

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
                [1, 'msg 3'],
            ]
        );

        $this->orm = $this->orm->withSchema(new Schema([
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
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFetchRelation()
    {
        $selector = new Selector($this->orm, User::class);
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
                    [
                        'id'      => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
            [
                'id'       => 2,
                'email'    => 'another@world.com',
                'balance'  => 200.0,
                'comments' => [],
            ],
        ], $selector->fetchData());
    }

    public function testPromised()
    {
        $selector = new Selector($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $this->captureReadQueries();
        $this->assertInstanceOf(PromisedCollection::class, $u->comments);
        $this->assertCount(3, $u->comments);
        $this->assertInstanceOf(Comment::class, $u->comments[0]);
        $this->assertNumReads(1);
    }

    public function testPromisedEmpty()
    {
        $selector = new Selector($this->orm, User::class);
        $u = $selector->wherePK(2)->fetchOne();

        $this->captureReadQueries();
        $this->assertInstanceOf(PromisedCollection::class, $u->comments);
        $this->assertCount(0, $u->comments);
        $this->assertNumReads(1);
    }

    public function testNoChanges()
    {
        $selector = new Selector($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $this->enableProfiling();

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();

        $this->assertNumWrites(0);
        $this->assertNumReads(0);
    }

    public function testRemoveChildren()
    {
        $selector = new Selector($this->orm, User::class);

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        $e->comments->remove(1);

        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        $this->assertCount(2, $e->comments);

        $this->assertSame('msg 1', $e->comments[0]->message);
        $this->assertSame('msg 3', $e->comments[1]->message);
    }

    public function testAddAndRemoveChildren()
    {
        $selector = new Selector($this->orm, User::class);

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        $e->comments->remove(1);

        $c = new Comment();
        $c->message = "msg 4";
        $e->comments->add($c);

        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        $this->assertCount(3, $e->comments);

        $this->assertSame('msg 1', $e->comments[0]->message);
        $this->assertSame('msg 3', $e->comments[1]->message);
        $this->assertSame('msg 4', $e->comments[2]->message);
    }

    public function testSliceAndSaveToAnotherParent()
    {
        $selector = new Selector($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->assertCount(3, $a->comments);
        $this->assertCount(0, $b->comments);

        $b->comments = $a->comments->slice(0, 2);

        foreach ($b->comments as $c) {
            $a->comments->removeElement($c);
        }

        $b->comments[0]->message = "new b";

        $this->assertCount(1, $a->comments);
        $this->assertCount(2, $b->comments);

        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('comments', [
            'method' => RelationLoader::INLOAD,
            'alias'  => 'comment'
        ])->orderBy('user.id')->orderBy('comment.id')->fetchAll();

        $this->assertCount(1, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->assertEquals(3, $a->comments[0]->id);
        $this->assertEquals(1, $b->comments[0]->id);
        $this->assertEquals(2, $b->comments[1]->id);

        $this->assertEquals('new b', $b->comments[0]->message);
    }
}