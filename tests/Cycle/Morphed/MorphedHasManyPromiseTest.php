<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Morphed;

use Doctrine\Common\Collections\Collection;
use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Tests\BaseTest;
use Spiral\Cycle\Tests\Fixtures\Comment;
use Spiral\Cycle\Tests\Fixtures\Post;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

abstract class MorphedHasManyPromiseTest extends BaseTest
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
            'message'     => 'string'
        ]);

        $this->getDatabase()->table('comment')->insertMultiple(
            ['parent_id', 'parent_type', 'message'],
            [
                [1, 'user', 'first comment'],
                [1, 'user', 'second comment'],
                [1, 'user', 'third comment'],

                [1, 'post', 'post 1 comment'],
                [2, 'post', 'post 2 comment'],
                [1, 'post', 'post 1.1 comment'],
                [2, 'post', 'post 2.1 comment'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
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
                        ],
                    ],
                    'posts'    => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Post::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ]
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
                        ],
                    ],
                ]
            ],
            Comment::class => [
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'parent_id', 'parent_type', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
        ]));
    }


    public function testAccessEntity()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $this->assertInstanceOf(Collection::class, $a->comments);
        $this->assertInstanceOf(Collection::class, $b->comments);

        $this->assertCount(3, $a->comments);
        $this->assertCount(0, $b->comments);

        $this->assertSame('first comment', $a->comments[0]->message);
        $this->assertSame('second comment', $a->comments[1]->message);
        $this->assertSame('third comment', $a->comments[2]->message);
    }

    public function testNoWrite()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testDeleteComment()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $a->comments->remove(0);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();
        $this->assertNumWrites(1);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $this->assertCount(2, $a->comments);
        $this->assertCount(0, $b->comments);
    }

    public function testMoveToAnotherParent()
    {
        /**
         * @var User $a
         * @var User $b
         */
        $selector = new Selector($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $b->comments = $a->comments->slice(0, 1);
        foreach ($b->comments as $c) {
            $a->comments->removeElement($c);
        }

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();
        $this->assertNumWrites(1);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $this->assertCount(2, $a->comments);
        $this->assertCount(1, $b->comments);

        $this->assertSame('first comment', $b->comments[0]->message);
        $this->assertSame('second comment', $a->comments[0]->message);
        $this->assertSame('third comment', $a->comments[1]->message);
    }

    public function testMoveToAnother()
    {
        /**
         * @var User $a
         * @var Post $b
         */
        $a = (new Selector($this->orm, User::class))->wherePK(1)->fetchOne();
        $b = (new Selector($this->orm, Post::class))->wherePK(1)->fetchOne();

        $b->comments->add($a->comments[0]);
        $a->comments->removeElement($a->comments[0]);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();
        $this->assertNumWrites(1);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $a = (new Selector($this->orm, User::class))->wherePK(1)->fetchOne();
        $b = (new Selector($this->orm, Post::class))->wherePK(1)->fetchOne();

        $this->assertCount(2, $a->comments);
        $this->assertCount(3, $b->comments);


        $this->assertTrue($a->comments->exists(function ($i, $c) {
            return $c->message == 'third comment';
        }));

        $this->assertTrue($a->comments->exists(function ($i, $c) {
            return $c->message == 'second comment';
        }));

        $this->assertTrue($b->comments->exists(function ($i, $c) {
            return $c->message == 'first comment';
        }));

        $this->assertTrue($b->comments->exists(function ($i, $c) {
            return $c->message == 'post 1 comment';
        }));

        $this->assertTrue($b->comments->exists(function ($i, $c) {
            return $c->message == 'post 1.1 comment';
        }));
    }
}