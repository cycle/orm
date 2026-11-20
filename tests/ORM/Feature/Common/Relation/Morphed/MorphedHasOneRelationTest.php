<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class MorphedHasOneRelationTest extends OrmTestCase
{
    #[Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('image')->orderBy('user.id');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'image' => [
                    'id' => 1,
                    'parent_id' => 1,
                    'parent_type' => 'user',
                    'url' => 'user-image.png',
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'image' =>
                    [
                        'id' => 3,
                        'parent_id' => 2,
                        'parent_type' => 'user',
                        'url' => 'user-2-image.png',
                    ],
            ],
        ]);
    }

    #[Test]
    public function testFetchRelationAnother(): void
    {
        $selector = new Select($this->orm, Post::class);
        $selector->load('image')->orderBy('post.id');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'user_id' => 1,
                'title' => 'post 1',
                'content' => 'post 1 body',
                'image' => [
                    'id' => 2,
                    'parent_id' => 1,
                    'parent_type' => 'post',
                    'url' => 'post-image.png',
                ],
            ],
            [
                'id' => 2,
                'user_id' => 1,
                'title' => 'post 2',
                'content' => 'post 2 body',
                'image' => [
                    'id' => 4,
                    'parent_id' => 2,
                    'parent_type' => 'post',
                    'url' => 'post-2-image.png',
                ],
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'title' => 'post 3',
                'content' => 'post 3 body',
                'image' => [
                    'id' => 5,
                    'parent_id' => 3,
                    'parent_type' => 'post',
                    'url' => 'post-3-image.png',
                ],
            ],
            [
                'id' => 4,
                'user_id' => 2,
                'title' => 'post 4',
                'content' => 'post 4 body',
                'image' => null,
            ],
        ]);
    }

    #[Test]
    public function testFetchOverlapping(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('image')
            ->load('posts.image')
            ->orderBy('user.id');


        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'image' => [
                    'id' => 1,
                    'parent_id' => 1,
                    'parent_type' => 'user',
                    'url' => 'user-image.png',
                ],
                'posts' => [
                    [
                        'id' => 1,
                        'user_id' => 1,
                        'title' => 'post 1',
                        'content' => 'post 1 body',
                        'image' => [
                            'id' => 2,
                            'parent_id' => 1,
                            'parent_type' => 'post',
                            'url' => 'post-image.png',
                        ],
                    ],
                    [
                        'id' => 2,
                        'user_id' => 1,
                        'title' => 'post 2',
                        'content' => 'post 2 body',
                        'image' => [
                            'id' => 4,
                            'parent_id' => 2,
                            'parent_type' => 'post',
                            'url' => 'post-2-image.png',
                        ],
                    ],
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'image' => [
                    'id' => 3,
                    'parent_id' => 2,
                    'parent_type' => 'user',
                    'url' => 'user-2-image.png',
                ],
                'posts' => [
                    [
                        'id' => 3,
                        'user_id' => 2,
                        'title' => 'post 3',
                        'content' => 'post 3 body',
                        'image' => [
                            'id' => 5,
                            'parent_id' => 3,
                            'parent_type' => 'post',
                            'url' => 'post-3-image.png',
                        ],
                    ],
                    [
                        'id' => 4,
                        'user_id' => 2,
                        'title' => 'post 4',
                        'content' => 'post 4 body',
                        'image' => null,
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function testAccessEntity(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('image')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        Assert::instanceOf($a->image, Image::class);
        Assert::instanceOf($b->image, Image::class);

        Assert::same($a->image->url, 'user-image.png');
        Assert::same($b->image->url, 'user-2-image.png');
    }

    #[Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('image')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);
    }

    #[Test]
    public function testSetNull(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('image')->orderBy('user.id');
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
        $selector->load('image')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        Assert::same($a->image, null);
        Assert::same($b->image->url, 'user-2-image.png');
    }

    #[Test]
    public function testExchangeParentsSameType(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('image')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        [$a->image, $b->image] = [$b->image, $a->image];

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(2);
        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->load('image')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        Assert::same($b->image->url, 'user-image.png');
        Assert::same($a->image->url, 'user-2-image.png');
    }

    #[Test]
    public function testReplaceExisted(): void
    {
        $count = (new Select($this->orm, Image::class))->count();

        $selector = new Select($this->orm, User::class);
        $selector->load('image');
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
        $selector->load('image');
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

        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(2);
        // consecutive
        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $p = (new Select($this->orm, Post::class))
            ->load('image')
            ->wherePK(5)->fetchOne();

        Assert::same($p->title, 'post title');
        Assert::same($p->image->url, 'new-post.png');
    }

    #[Test]
    public function testCreateWithRelatedAndBelongsToUser(): void
    {
        $schemaArray = $this->getSchemaArray();
        $schemaArray[Post::class][Schema::RELATIONS]['user'] = [
            Relation::TYPE => Relation::BELONGS_TO,
            Relation::TARGET => User::class,
            Relation::SCHEMA => [
                Relation::CASCADE => true,
                Relation::INNER_KEY => 'user_id',
                Relation::OUTER_KEY => 'id',
            ],
        ];
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $p = new Post();
        $p->title = 'post title';
        $p->content = 'post content';

        $p->user = new User();
        $p->user->balance = 100;
        $p->user->email = 'email';

        $p->image = new Image();
        $p->image->url = 'new-post.png';

        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(3);
        // consecutive
        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $p = (new Select($this->orm, Post::class))
            ->load('image')
            ->wherePK(5)->fetchOne();

        Assert::same($p->title, 'post title');
        Assert::same($p->image->url, 'new-post.png');
    }

    #[Test]
    public function testMoveToAnotherParent(): void
    {
        $u = (new Select($this->orm, User::class))->load('image')->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->load('image')->fetchOne(['post.id' => 1]);

        $u->image = $p->image;
        $p->image = null;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(2);

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->load('image')->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->load('image')->fetchOne(['post.id' => 1]);

        Assert::same($u->image->url, 'post-image.png');
        Assert::same($p->image, null);
    }

    #[Test]
    public function testChangeParents(): void
    {
        $u = (new Select($this->orm, User::class))->load('image')->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->load('image')->fetchOne(['post.id' => 2]);

        [$u->image, $p->image] = [$p->image, $u->image];

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(2);
        // no changes expected
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, User::class))->load('image')->fetchOne(['user.id' => 1]);
        $p = (new Select($this->orm, Post::class))->load('image')->fetchOne(['post.id' => 2]);

        Assert::same($u->image->url, 'post-2-image.png');
        Assert::same($p->image->url, 'user-image.png');
    }

    #[Test]
    public function testUpdateRelation(): void
    {
        $this->captureReadQueries();
        $users = (new Select($this->orm, User::class))->fetchAll();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))->collect(...$users)->load('image')->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        foreach ($users as $user) {
            Assert::notNull($user->image);
        }
        $this->assertNumReads(0);
    }

    #[Test]
    public function testUpdateNestedRelation(): void
    {
        $this->captureReadQueries();
        $users = (new Select($this->orm, User::class))->fetchAll();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))->collect(...$users)
            ->load('posts.image')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        foreach ($users as $user) {
            Assert::iterable($user->posts);
            foreach ($user->posts as $post) {
                // Post 4 doesn't have an image
                if ($post->id !== 4) {
                    Assert::notNull($post->image);
                    Assert::instanceOf($post->image, Image::class);
                }
            }
        }
        $this->assertNumReads(0);
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
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
                Schema::TYPECAST => [
                    'id' => 'int',
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
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type',
                        ],
                    ],
                ],
                Schema::TYPECAST => [
                    'id' => 'int',
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
                Schema::TYPECAST => [
                    'id' => 'int',
                ],
            ],
        ];
    }
}
