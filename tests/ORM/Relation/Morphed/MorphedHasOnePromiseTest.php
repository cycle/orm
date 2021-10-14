<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class MorphedHasOnePromiseTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('post', [
            'id' => 'primary',
            'user_id' => 'integer,nullable',
            'title' => 'string',
            'content' => 'string',
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
            'id' => 'primary',
            'parent_id' => 'integer',
            'parent_type' => 'string',
            'url' => 'string',
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

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    public function testAccessEntity(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();
        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        $this->assertInstanceOf(ReferenceInterface::class, $aData['image']);
        $this->assertInstanceOf(ReferenceInterface::class, $bData['image']);

        $this->captureReadQueries();
        $this->assertSame('user-image.png', $a->image->url);
        $this->assertSame('user-2-image.png', $b->image->url);
        $this->assertNumReads(2);
    }

    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(0);
    }

    public function testSetNull(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $a->image = null;

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        $this->assertInstanceOf(ReferenceInterface::class, $aData['image']);
        $this->assertInstanceOf(ReferenceInterface::class, $bData['image']);
        $this->assertSame('user-2-image.png', $b->image->url);
    }

    public function testExchangeParentsSameTypeUsingReferences(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        $this->captureReadQueries();
        [$a->image, $b->image] = [$bData['image'], $aData['image']];
        $this->assertNumReads(0);

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumReads(2);
        $this->assertNumWrites(2);

        // consecutive
        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumReads(0);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->assertSame('user-image.png', $b->image->url);
        $this->assertSame('user-2-image.png', $a->image->url);
    }

    public function testExchangeParentsSameType(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        [$a->image, $b->image] = [$b->image, $a->image];

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumReads(0);
        $this->assertNumWrites(2);

        // consecutive
        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumReads(0);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $this->assertSame('user-image.png', $b->image->url);
        $this->assertSame('user-2-image.png', $a->image->url);
    }

    public function testReplaceExisted(): void
    {
        $count = (new Select($this->orm, Image::class))->count();

        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $u->image = new Image();
        $u->image->url = 'new.png';

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $this->assertSame('new.png', $u->image->url);
        $this->assertSame($count, (new Select($this->orm, Image::class))->count());
    }

    public function testCreateWithRelated(): void
    {
        $schemaArray = $this->getSchemaArray();
        $schemaArray[User::class][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::NULLABLE] = true;
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $p = new Post();
        $p->title = 'post title';
        $p->content = 'post content';

        $p->image = new Image();
        $p->image->url = 'new-post.png';

        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $p = (new Select($this->orm, Post::class))
            ->wherePK(5)->fetchOne();

        $this->assertSame('post title', $p->title);
        $this->assertSame('new-post.png', $p->image->url);
    }

    public function testMoveToAnotherParent(): void
    {
        /** @var User $u */
        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        /** @var Post $p */
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 1]);

        $this->captureReadQueries();
        $u->image = $p->image;
        $p->image = null;
        // Resolve $p->image reference
        $this->assertNumReads(1);

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($u, $p);
        $this->assertNumWrites(2);
        // Resolve $u->image reference in the Node
        $this->assertNumReads(1);

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($u, $p);
        $this->assertNumWrites(0);
        $this->assertNumReads(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 1]);

        $this->assertSame('post-image.png', $u->image->url);
        $this->assertNull($p->image);
    }

    public function testChangeParentsUsingReferences(): void
    {
        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        $uData = $this->extractEntity($u);
        $pData = $this->extractEntity($p);

        $this->captureReadQueries();
        [$u->image, $p->image] = [$pData['image'], $uData['image']];
        $this->assertNumReads(0);

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($u, $p);
        $this->assertNumWrites(2);
        $this->assertNumReads(2);

        // no changes expected
        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($u, $p);
        $this->assertNumWrites(0);
        $this->assertNumReads(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        $this->assertSame('post-2-image.png', $u->image->url);
        $this->assertSame('user-image.png', $p->image->url);
    }

    public function testChangeParents(): void
    {
        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        [$u->image, $p->image] = [$p->image, $u->image];

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($u, $p);
        $this->assertNumWrites(2);
        $this->assertNumReads(0);

        // no changes expected
        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($u, $p);
        $this->assertNumWrites(0);
        $this->assertNumReads(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        $this->assertSame('post-2-image.png', $u->image->url);
        $this->assertSame('user-image.png', $p->image->url);
    }

    private function getSchemaArray(): array
    {
        return [
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'image' => [
                        Relation::TYPE => Relation::MORPHED_HAS_ONE,
                        Relation::TARGET => Image::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type',
                        ],
                    ],
                    'posts' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Post::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Post::class => [
                Schema::ROLE => 'post',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'title', 'content'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'image' => [
                        Relation::TYPE => Relation::MORPHED_HAS_ONE,
                        Relation::TARGET => Image::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type',
                        ],
                    ],
                ],
            ],
            Image::class => [
                Schema::ROLE => 'image',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'image',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'parent_id', 'parent_type', 'url'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ];
    }
}
