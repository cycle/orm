<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\MorphedHasOneLoadOptions;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class MorphedHasOneLoadOptionsTest extends BaseTest
{
    use TableTrait;

    public function testLoadWithSingleQueryMethod(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->load('image', new MorphedHasOneLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id')->fetchAll();

        $this->assertSame('user-image.png', $a->image->url);
        $this->assertSame('user-2-image.png', $b->image->url);
    }

    public function testLoadWithOuterQueryMethod(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->load('image', new MorphedHasOneLoadOptions(
            method: LoadMethod::OuterQuery,
        ))->orderBy('user.id')->fetchAll();

        $this->assertSame('user-image.png', $a->image->url);
        $this->assertSame('user-2-image.png', $b->image->url);
    }

    public function testLoadWithDefaultOptions(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->load('image', new MorphedHasOneLoadOptions())
            ->orderBy('user.id')->fetchAll();

        $this->assertSame('user-image.png', $a->image->url);
        $this->assertSame('user-2-image.png', $b->image->url);
    }

    public function testLoadFromArchiveTable(): void
    {
        $this->makeTable('image_archive', [
            'id' => 'primary',
            'parent_id' => 'integer',
            'parent_type' => 'string',
            'url' => 'string',
        ]);

        $this->getDatabase()->table('image_archive')->insertMultiple(
            ['parent_id', 'parent_type', 'url'],
            [
                [1, 'user', 'archived-user-image.png'],
                [2, 'user', 'archived-user-2-image.png'],
            ],
        );

        [$a, $b] = (new Select($this->orm, User::class))->load('image', new MorphedHasOneLoadOptions(
            table: 'image_archive',
        ))->orderBy('user.id')->fetchAll();

        $this->assertSame('archived-user-image.png', $a->image->url);
        $this->assertSame('archived-user-2-image.png', $b->image->url);
    }

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
            ],
        );

        $this->orm = $this->withSchema(new Schema([
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
                ],
                Schema::SCOPE => SortByIDScope::class,
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
        ]));
    }
}
