<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\ImagedInterface;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

// Belongs to morphed relation does not support eager loader, this relation can only work using lazy loading
// and promises.
abstract class BelongsToMorphedRelationTest extends BaseTest
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
            'parent_id' => 'integer,nullable',
            'parent_type' => 'string,nullable',
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

    public function testGetParent(): void
    {
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $cData = $this->extractEntity($c);

        $this->assertInstanceOf(ReferenceInterface::class, $cData['parent']);

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $c->parent);
        $this->assertSame('hello@world.com', $c->parent->email);
        $this->assertNumReads(1);
    }

    public function testNoWritesNotLoaded(): void
    {
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $cData = $this->extractEntity($c);

        $this->assertInstanceOf(ReferenceInterface::class, $cData['parent']);

        $this->captureWriteQueries();
        $this->save($c);
        $this->assertNumWrites(0);
    }

    public function testGetParentLoaded(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);

        $c = $this->orm->getRepository(Image::class)->findByPK(1);

        $this->assertInstanceOf(User::class, $c->parent);
        $this->assertSame('hello@world.com', $c->parent->email);
    }

    public function testNoWritesLoaded(): void
    {
        // $schemaArray = $this->getSchemaArray();
        // $schemaArray[User::class][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::NULLABLE] = true;
        // $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $cData = $this->extractEntity($c);

        $this->assertInstanceOf(ReferenceInterface::class, $cData['parent']);

        $this->assertInstanceOf(User::class, $c->parent);

        $this->captureWriteQueries();
        $this->save($c);
        $this->assertNumWrites(0);
    }

    public function testGetParentPostloaded(): void
    {
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $cData = $this->extractEntity($c);

        $this->assertInstanceOf(ReferenceInterface::class, $cData['parent']);

        $u = $this->orm->getRepository(User::class)->findByPK(1);

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $c->parent);
        $this->assertSame('hello@world.com', $c->parent->email);
        $this->assertNumReads(0);
    }

    public function testCreateWithMorphedExistedParent(): void
    {
        $schemaArray = $this->getNullableMorphedSchemaArray();
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = new Image();
        $c->url = 'test.png';

        $c->parent = $this->orm->getRepository(User::class)->findByPK(1);

        $this->captureWriteQueries();
        $this->save($c);
        $this->assertNumWrites(1);

        // consecutive
        $this->captureWriteQueries();
        $this->save($c);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $c = $this->orm->getRepository(Image::class)->findByPK(6);
        $cData = $this->extractEntity($c);

        $this->assertInstanceOf(ReferenceInterface::class, $cData['parent']);

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $c->parent);
        $this->assertSame('hello@world.com', $c->parent->email);
        $this->assertNumReads(1);
    }

    public function testCreateWithNewParent(): void
    {
        $schemaArray = $this->getSchemaArray();
        $schemaArray[User::class][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::NULLABLE] = true;
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = new Image();
        $c->url = 'test.png';

        $c->parent = new Post();
        $c->parent->title = 'post title';
        $c->parent->content = 'post content';

        $this->captureWriteQueries();
        $this->save($c);
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $this->save($c);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $c = $this->orm->getRepository(Image::class)->findByPK(6);
        $cData = $this->extractEntity($c);

        $this->assertInstanceOf(ReferenceInterface::class, $cData['parent']);

        $this->captureReadQueries();
        $this->assertInstanceOf(Post::class, $c->parent);
        $this->assertSame('post title', $c->parent->title);
        $this->assertNumReads(1);
    }

    public function testSetParentAndUpdateParent(): void
    {
        $c = new Image();
        $c->url = 'test.png';

        $c->parent = $this->orm->getRepository(User::class)->findByPK(1);
        $c->parent->balance = 777;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($c);
        $tr->run();
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($c);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $c = $this->orm->getRepository(Image::class)->findByPK(6);
        $cData = $this->extractEntity($c);

        $this->assertInstanceOf(ReferenceInterface::class, $cData['parent']);

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $c->parent);
        $this->assertSame('hello@world.com', $c->parent->email);
        $this->assertEquals(777, $c->parent->balance);
        $this->assertNumReads(1);
    }

    public function testChangeParentWithLoading(): void
    {
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        $this->assertInstanceOf(User::class, $c1->parent);
        $this->assertInstanceOf(Post::class, $c2->parent);

        [$c1->parent, $c2->parent] = [$c2->parent, $c1->parent];

        $this->captureWriteQueries();
        $this->save($c1, $c2);
        $this->assertNumWrites(2);

        $this->orm = $this->orm->withHeap(new Heap());
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        $this->assertInstanceOf(Post::class, $c1->parent);
        $this->assertInstanceOf(User::class, $c2->parent);
    }

    public function testChangeParentWithoutLoading(): void
    {
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        [$c1->parent, $c2->parent] = [$c2->parent, $c1->parent];

        $this->captureWriteQueries();
        $this->save($c1, $c2);
        $this->assertNumWrites(2);

        $this->orm = $this->orm->withHeap(new Heap());
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        $this->assertInstanceOf(Post::class, $c1->parent);
        $this->assertInstanceOf(User::class, $c2->parent);
    }

    public function testChangeParentLoadedAfter(): void
    {
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        $this->orm->getRepository(User::class)->findByPK(1);
        $this->orm->getRepository(Post::class)->findByPK(1);

        [$c1->parent, $c2->parent] = [$c2->parent, $c1->parent];

        $this->captureWriteQueries();
        $this->save($c1, $c2);
        $this->assertNumWrites(2);

        $this->orm = $this->orm->withHeap(new Heap());
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        $this->assertInstanceOf(Post::class, $c1->parent);
        $this->assertInstanceOf(User::class, $c2->parent);
    }

    public function testSetNull(): void
    {
        $schemaArray = $this->getNullableMorphedSchemaArray();
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $c->parent = null;

        $this->captureWriteQueries();
        $this->save($c);
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $this->assertNull($c->parent);
    }

    private function getNullableMorphedSchemaArray(): array
    {
        $schemaArray = $this->getSchemaArray();
        $schemaArray[User::class][Schema::RELATIONS]['image'][Relation::SCHEMA][Relation::NULLABLE] = true;
        $schemaArray[Post::class][Schema::RELATIONS]['image'][Relation::SCHEMA][Relation::NULLABLE] = true;
        return $schemaArray;
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
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parentId',
                            Relation::MORPH_KEY => 'parentType',
                        ],
                    ],
                    'posts' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Post::class,
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
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parentId',
                            Relation::MORPH_KEY => 'parentType',
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
                Schema::COLUMNS => [
                    'id' => 'id',
                    'parentId' => 'parent_id',
                    'parentType' => 'parent_type',
                    'url' => 'url',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'parent' => [
                        Relation::TYPE => Relation::BELONGS_TO_MORPHED,
                        Relation::TARGET => ImagedInterface::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,

                        Relation::SCHEMA => [
                            Relation::NULLABLE => true,
                            Relation::CASCADE => true,
                            Relation::OUTER_KEY => 'id',
                            Relation::INNER_KEY => 'parentId',
                            Relation::MORPH_KEY => 'parentType',
                        ],
                    ],
                ],
            ],
        ];
    }
}
