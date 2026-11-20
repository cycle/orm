<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Options;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\ImagedInterface;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\Testing\Database\Assert\QueryAssert;

// Belongs to morphed relation does not support eager loader, this relation can only work using lazy loading
// and promises.
class BelongsToMorphedRelationTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testGetParent(): void
    {
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $cData = $this->extractEntity($c);

        \Testo\Assert::instanceOf($cData['parent'], ReferenceInterface::class);
        QueryAssert::reads(1, function () use ($c): void {
            \Testo\Assert::instanceOf($c->parent, User::class);
            \Testo\Assert::same($c->parent->email, 'hello@world.com');
        });
    }

    #[\Testo\Test]
    public function testNoWritesNotLoaded(): void
    {
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $cData = $this->extractEntity($c);

        \Testo\Assert::instanceOf($cData['parent'], ReferenceInterface::class);
        QueryAssert::writes(0, fn() => $this->save($c));
    }

    #[\Testo\Test]
    public function testGetParentLoaded(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);

        $c = $this->orm->getRepository(Image::class)->findByPK(1);

        \Testo\Assert::instanceOf($c->parent, User::class);
        \Testo\Assert::same($c->parent->email, 'hello@world.com');
    }

    #[\Testo\Test]
    public function testNoWritesLoaded(): void
    {
        // $schemaArray = $this->getSchemaArray();
        // $schemaArray[User::class][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::NULLABLE] = true;
        // $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $cData = $this->extractEntity($c);

        \Testo\Assert::instanceOf($cData['parent'], ReferenceInterface::class);

        \Testo\Assert::instanceOf($c->parent, User::class);
        QueryAssert::writes(0, fn() => $this->save($c));
    }

    #[\Testo\Test]
    public function testGetParentPostloaded(): void
    {
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $cData = $this->extractEntity($c);

        \Testo\Assert::instanceOf($cData['parent'], ReferenceInterface::class);

        $u = $this->orm->getRepository(User::class)->findByPK(1);
        QueryAssert::reads(0, function () use ($c): void {
            \Testo\Assert::instanceOf($c->parent, User::class);
            \Testo\Assert::same($c->parent->email, 'hello@world.com');
        });
    }

    #[\Testo\Test]
    public function testCreateWithMorphedExistedParent(): void
    {
        $schemaArray = $this->getNullableMorphedSchemaArray();
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = new Image();
        $c->url = 'test.png';

        $c->parent = $this->orm->getRepository(User::class)->findByPK(1);
        QueryAssert::writes(1, fn() => $this->save($c));
        // consecutive
        QueryAssert::writes(0, fn() => $this->save($c));

        $this->orm = $this->orm->withHeap(new Heap());

        $c = $this->orm->getRepository(Image::class)->findByPK(6);
        $cData = $this->extractEntity($c);

        \Testo\Assert::instanceOf($cData['parent'], ReferenceInterface::class);
        QueryAssert::reads(1, function () use ($c): void {
            \Testo\Assert::instanceOf($c->parent, User::class);
            \Testo\Assert::same($c->parent->email, 'hello@world.com');
        });
    }

    #[\Testo\Test]
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
        QueryAssert::writes(2, fn() => $this->save($c));
        // consecutive
        QueryAssert::writes(0, fn() => $this->save($c));

        $this->orm = $this->orm->withHeap(new Heap());

        $c = $this->orm->getRepository(Image::class)->findByPK(6);
        $cData = $this->extractEntity($c);

        \Testo\Assert::instanceOf($cData['parent'], ReferenceInterface::class);
        QueryAssert::reads(1, function () use ($c): void {
            \Testo\Assert::instanceOf($c->parent, Post::class);
            \Testo\Assert::same($c->parent->title, 'post title');
        });
    }

    #[\Testo\Test]
    public function testSetParentAndUpdateParent(): void
    {
        $c = new Image();
        $c->url = 'test.png';

        $c->parent = $this->orm->getRepository(User::class)->findByPK(1);
        $c->parent->balance = 777;
        QueryAssert::writes(2, function () use (&$tr, $c): void {
            $tr = new Transaction($this->orm);
            $tr->persist($c);
            $tr->run();
        });
        // consecutive
        QueryAssert::writes(0, function () use (&$tr, $c): void {
            $tr = new Transaction($this->orm);
            $tr->persist($c);
            $tr->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());

        $c = $this->orm->getRepository(Image::class)->findByPK(6);
        $cData = $this->extractEntity($c);

        \Testo\Assert::instanceOf($cData['parent'], ReferenceInterface::class);
        QueryAssert::reads(1, function () use ($c): void {
            \Testo\Assert::instanceOf($c->parent, User::class);
            \Testo\Assert::same($c->parent->email, 'hello@world.com');
            \Testo\Assert::equals($c->parent->balance, 777);
        });
    }

    #[\Testo\Test]
    public function testChangeParentWithLoading(): void
    {
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        \Testo\Assert::instanceOf($c1->parent, User::class);
        \Testo\Assert::instanceOf($c2->parent, Post::class);

        [$c1->parent, $c2->parent] = [$c2->parent, $c1->parent];
        QueryAssert::writes(2, fn() => $this->save($c1, $c2));

        $this->orm = $this->orm->withHeap(new Heap());
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        \Testo\Assert::instanceOf($c1->parent, Post::class);
        \Testo\Assert::instanceOf($c2->parent, User::class);
    }

    #[\Testo\Test]
    public function testChangeParentWithoutLoading(): void
    {
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        [$c1->parent, $c2->parent] = [$c2->parent, $c1->parent];
        QueryAssert::writes(2, fn() => $this->save($c1, $c2));

        $this->orm = $this->orm->withHeap(new Heap());
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        \Testo\Assert::instanceOf($c1->parent, Post::class);
        \Testo\Assert::instanceOf($c2->parent, User::class);
    }

    #[\Testo\Test]
    public function testChangeParentLoadedAfter(): void
    {
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        $this->orm->getRepository(User::class)->findByPK(1);
        $this->orm->getRepository(Post::class)->findByPK(1);

        [$c1->parent, $c2->parent] = [$c2->parent, $c1->parent];
        QueryAssert::writes(2, fn() => $this->save($c1, $c2));

        $this->orm = $this->orm->withHeap(new Heap());
        $c1 = $this->orm->getRepository(Image::class)->findByPK(1);
        $c2 = $this->orm->getRepository(Image::class)->findByPK(2);

        \Testo\Assert::instanceOf($c1->parent, Post::class);
        \Testo\Assert::instanceOf($c2->parent, User::class);
    }

    #[\Testo\Test]
    public function testSetNull(): void
    {
        $schemaArray = $this->getNullableMorphedSchemaArray();
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        $c->parent = null;
        QueryAssert::writes(1, fn() => $this->save($c));

        $this->orm = $this->orm->withHeap(new Heap());
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        \Testo\Assert::null($c->parent);
    }

    /**
     * When setting a morphed relation to null, both parent_id AND parent_type
     * should be cleared in the database.
     */
    #[\Testo\Test]
    public function testSetNullShouldClearMorphType(): void
    {
        $schemaArray = $this->getNullableMorphedSchemaArray();
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = $this->orm->getRepository(Image::class)->findByPK(1);

        // Verify the image initially has a parent_type
        $row = $this->getDatabase()->table('image')->select()->where('id', 1)->fetchAll();
        \Testo\Assert::same($row[0]['parent_type'], 'user');
        \Testo\Assert::notNull($row[0]['parent_id']);
        $this->save($c);

        $c->parent = null;
        $this->save($c);

        // After setting parent to null, both parent_id and parent_type should be NULL
        $row = $this->getDatabase()->table('image')->select()->where('id', 1)->fetchAll();
        \Testo\Assert::null($row[0]['parent_id'], 'parent_id should be NULL after setting relation to null');
        \Testo\Assert::null($row[0]['parent_type'], 'parent_type should be NULL after setting relation to null');
    }

    /**
     * When creating a new entity without setting the morphed relation,
     * parent_type should not be written to the database.
     */
    #[\Testo\Test]
    public function testCreateWithoutParentShouldNotSetMorphType(): void
    {
        $schemaArray = $this->getNullableMorphedSchemaArray();
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $c = new Image();
        $c->url = 'no-parent.png';

        $this->save($c);

        $row = $this->getDatabase()->table('image')->select()->where('id', 6)->fetchAll();
        \Testo\Assert::null($row[0]['parent_id'], 'parent_id should be NULL for entity without parent');
        \Testo\Assert::null($row[0]['parent_type'], 'parent_type should be NULL for entity without parent');
    }

    #[\Testo\Test]
    public function testUsingFactoryCreateWithoutRelatedButSetMorphTypeManually(): void
    {
        $schemaArray = $this->getNullableMorphedSchemaArray();
        $this->orm = $this->orm->with(
            schema: new Schema($schemaArray),
        );

        /** @var Image $c */
        $c = $this->orm->make(Image::class);
        $c->url = 'no-parent.png';
        $c->parentType = 'user';

        $this->save($c);

        $row = $this->getDatabase()->table('image')->select()->where('id', $c->id)->fetchAll();
        \Testo\Assert::null($row[0]['parent_id'], 'parent_id should be NULL for entity without parent');
        \Testo\Assert::same($row[0]['parent_type'], 'user', 'parent_type should be NULL for entity without parent');
    }

    #[\Testo\Test]
    public function testCreateWithoutRelatedButSetMorphTypeManually(): void
    {
        $schemaArray = $this->getNullableMorphedSchemaArray();
        $this->orm = $this->orm->with(
            schema: new Schema($schemaArray),
        );

        $c = new Image();
        $c->url = 'no-parent.png';
        $c->parentType = 'user';

        $this->save($c);

        $row = $this->getDatabase()->table('image')->select()->where('id', $c->id)->fetchAll();
        \Testo\Assert::null($row[0]['parent_id'], 'parent_id should be NULL for entity without parent');
        \Testo\Assert::same($row[0]['parent_type'], 'user', 'parent_type should be NULL for entity without parent');
    }

    #[\Testo\Test]
    public function testUpdateRelation(): void
    {
        $images = QueryAssert::reads(1, fn() => (new Select($this->orm, Image::class))->fetchAll());
        QueryAssert::reads(2, fn() => (new BulkLoader($this->orm))->collect(...$images)->load('parent')->run());
        QueryAssert::reads(0, function () use ($images): void {
            foreach ($images as $image) {
                \Testo\Assert::notNull($image->parent);
            }
        });
    }

    /**
     * With ignoreUninitializedRelations = true (BaseTest default) unsetting the relation property
     * must leave both the outer key and the morph key untouched.
     */
    #[\Testo\Test]
    public function testUnsetParentKeepsMorphWhenIgnoringUninitialized(): void
    {
        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        \Testo\Assert::instanceOf($c->parent, User::class);
        unset($c->parent);
        QueryAssert::writes(0, fn() => $this->save($c));

        $row = $this->getDatabase()->table('image')->select()->where('id', 1)->fetchAll();
        \Testo\Assert::same((string) $row[0]['parent_id'], '1');
        \Testo\Assert::same($row[0]['parent_type'], 'user');
    }

    /**
     * With ignoreUninitializedRelations = false an unset relation is treated as null, so both the
     * outer key and the morph key must be cleared.
     */
    #[\Testo\Test]
    public function testUnsetParentClearsMorphWithoutIgnoreUninitialized(): void
    {
        $this->orm = $this->withSchema(new Schema($this->getNullableMorphedSchemaArray()))
            ->with(options: (new Options())->withIgnoreUninitializedRelations(false));

        $c = $this->orm->getRepository(Image::class)->findByPK(1);
        \Testo\Assert::instanceOf($c->parent, User::class);
        unset($c->parent);
        QueryAssert::writes(1, fn() => $this->save($c));

        $row = $this->getDatabase()->table('image')->select()->where('id', 1)->fetchAll();
        \Testo\Assert::null($row[0]['parent_id'], 'parent_id should be NULL when the unset relation is treated as null');
        \Testo\Assert::null($row[0]['parent_type'], 'parent_type should be NULL when the unset relation is treated as null');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
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
            ],
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
            ],
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
            ],
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
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
