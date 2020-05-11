<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class MorphedHasOnePromiseTest extends BaseTest
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

        $this->makeTable('image', [
            'id'          => 'primary',
            'parent_id'   => 'integer',
            'parent_type' => 'string',
            'url'         => 'string'
        ]);

        $this->getDatabase()->table('image')->insertMultiple(
            ['parent_id', 'parent_type', 'url'],
            [
                [1, 'user', 'user-image.png'],
                [1, 'post', 'post-image.png'],
                [2, 'user', 'user-2-image.png'],
                [2, 'post', 'post-2-image.png'],
                [3, 'post', 'post-3-image.png'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class  => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'image' => [
                        Relation::TYPE   => Relation::MORPHED_HAS_ONE,
                        Relation::TARGET => Image::class,
                        Relation::LOAD   => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type'
                        ],
                    ],
                    'posts' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Post::class,
                        Relation::LOAD   => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id'
                        ]
                    ]
                ]
            ],
            Post::class  => [
                Schema::ROLE        => 'post',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'title', 'content'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'image' => [
                        Relation::TYPE   => Relation::MORPHED_HAS_ONE,
                        Relation::TARGET => Image::class,
                        Relation::LOAD   => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type'
                        ],
                    ]
                ]
            ],
            Image::class => [
                Schema::ROLE        => 'image',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'image',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'parent_id', 'parent_type', 'url'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
        ]));
    }

    public function testAccessEntity(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->assertInstanceOf(PromiseInterface::class, $a->image);
        $this->assertInstanceOf(PromiseInterface::class, $b->image);

        $this->captureReadQueries();
        $this->assertSame('user-image.png', $a->image->__resolve()->url);
        $this->assertSame('user-2-image.png', $b->image->__resolve()->url);
        $this->assertNumReads(2);
    }

    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testSetNull(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $a->image = null;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->assertInstanceOf(PromiseInterface::class, $a->image);
        $this->assertSame('user-2-image.png', $b->image->__resolve()->url);
    }

    public function testExchangeParentsSameType(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        [$a->image, $b->image] = [$b->image, $a->image];

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumReads(2);
        $this->assertNumWrites(2);

        // consecutive
        $this->captureReadQueries();
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumReads(0);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->assertSame('user-image.png', $b->image->__resolve()->url);
        $this->assertSame('user-2-image.png', $a->image->__resolve()->url);
    }

    public function testReplaceExisted(): void
    {
        $count = (new Select($this->orm, Image::class))->count();

        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $u->image = new Image();
        $u->image->url = 'new.png';

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $this->assertSame('new.png', $u->image->__resolve()->url);
        $this->assertSame($count, (new Select($this->orm, Image::class))->count());
    }

    public function testCreateWithRelated(): void
    {
        $p = new Post();
        $p->title = 'post title';
        $p->content = 'post content';

        $p->image = new Image();
        $p->image->url = 'new-post.png';

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Post::class);
        $p = $selector->wherePK(5)->fetchOne();

        $this->assertSame('post title', $p->title);
        $this->assertSame('new-post.png', $p->image->__resolve()->url);
    }

    public function testMoveToAnotherParent(): void
    {
        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 1]);

        $u->image = $p->image;
        $p->image = null;

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(2);
        $this->assertNumReads(2);

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 1]);

        $this->assertSame('post-image.png', $u->image->__resolve()->url);
        $this->assertSame(null, $p->image->__resolve());
    }


    public function testChangeParents(): void
    {
        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        [$u->image, $p->image] = [$p->image, $u->image];

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(2);
        $this->assertNumReads(2);

        // no changes expected
        $this->captureReadQueries();
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(0);
        $this->assertNumReads(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        $this->assertSame('post-2-image.png', $u->image->__resolve()->url);
        $this->assertSame('user-image.png', $p->image->__resolve()->url);
    }
}
