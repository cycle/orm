<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class MorphedHasOnePromiseTest extends OrmTestCase
{
    #[Test]
    public function testAccessEntity(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();
        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['image'], ReferenceInterface::class);
        Assert::instanceOf($bData['image'], ReferenceInterface::class);

        QueryAssert::reads(2, function () use ($a, $b): void {
            Assert::same($a->image->url, 'user-image.png');
            Assert::same($b->image->url, 'user-2-image.png');
        });
    }

    #[Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        QueryAssert::writes(0, fn() => $this->save($a, $b));
    }

    #[Test]
    public function testSetNull(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $a->image = null;

        QueryAssert::writes(1, fn() => $this->save($a, $b));

        QueryAssert::writes(0, fn() => $this->save($a, $b));

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['image'], ReferenceInterface::class);
        Assert::instanceOf($bData['image'], ReferenceInterface::class);
        Assert::same($b->image->url, 'user-2-image.png');
    }

    #[Test]
    public function testExchangeParentsSameTypeUsingReferences(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        QueryAssert::reads(0, fn() => [$a->image, $b->image] = [$bData['image'], $aData['image']]);

        QueryAssert::count(2, 2, fn() => $this->save($a, $b));

        // consecutive
        QueryAssert::count(0, 0, fn() => $this->save($a, $b));

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        Assert::same($b->image->url, 'user-image.png');
        Assert::same($a->image->url, 'user-2-image.png');
    }

    #[Test]
    public function testExchangeParentsSameType(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        [$a->image, $b->image] = [$b->image, $a->image];

        QueryAssert::count(0, 2, fn() => $this->save($a, $b));

        // consecutive
        QueryAssert::count(0, 0, fn() => $this->save($a, $b));

        $this->orm = $this->orm->withHeap(new Heap());
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        Assert::same($b->image->url, 'user-image.png');
        Assert::same($a->image->url, 'user-2-image.png');
    }

    #[Test]
    public function testReplaceExisted(): void
    {
        $count = (new Select($this->orm, Image::class))->count();

        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $u->image = new Image();
        $u->image->url = 'new.png';

        QueryAssert::writes(2, fn() => $this->save($u));

        // consecutive
        QueryAssert::writes(0, fn() => $this->save($u));

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        Assert::same($u->image->url, 'new.png');
        Assert::same((new Select($this->orm, Image::class))->count(), $count);
    }

    #[Test]
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

        QueryAssert::writes(2, fn() => $this->save($p));

        // consecutive
        QueryAssert::writes(0, fn() => $this->save($p));

        $this->orm = $this->orm->withHeap(new Heap());
        $p = (new Select($this->orm, Post::class))
            ->wherePK(5)->fetchOne();

        Assert::same($p->title, 'post title');
        Assert::same($p->image->url, 'new-post.png');
    }

    #[Test]
    public function testMoveToAnotherParent(): void
    {
        /** @var User $u */
        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        /** @var Post $p */
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 1]);

        QueryAssert::reads(1, function () use ($u, $p): void {
            $u->image = $p->image;
            $p->image = null;
        });
        QueryAssert::count(1, 2, fn() => $this->save($u, $p));

        QueryAssert::count(0, 0, fn() => $this->save($u, $p));

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 1]);

        Assert::same($u->image->url, 'post-image.png');
        Assert::null($p->image);
    }

    #[Test]
    public function testChangeParentsUsingReferences(): void
    {
        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        $uData = $this->extractEntity($u);
        $pData = $this->extractEntity($p);

        QueryAssert::reads(0, fn() => [$u->image, $p->image] = [$pData['image'], $uData['image']]);

        QueryAssert::count(2, 2, fn() => $this->save($u, $p));

        // no changes expected
        QueryAssert::count(0, 0, fn() => $this->save($u, $p));

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        Assert::same($u->image->url, 'post-2-image.png');
        Assert::same($p->image->url, 'user-image.png');
    }

    #[Test]
    public function testChangeParents(): void
    {
        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        [$u->image, $p->image] = [$p->image, $u->image];

        QueryAssert::count(0, 2, fn() => $this->save($u, $p));

        // no changes expected
        QueryAssert::count(0, 0, fn() => $this->save($u, $p));

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->fetchOne(['post.id' => 2]);

        Assert::same($u->image->url, 'post-2-image.png');
        Assert::same($p->image->url, 'user-image.png');
    }

    #[BeforeTest]
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
            ],
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
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
