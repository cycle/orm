<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany;

use Cycle\ORM\Collection\LoophpCollectionFactory;
use Cycle\ORM\Collection\Pivoted\LoophpPivotedCollection;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class ManyToManyLoophpPivotTest extends BaseTest
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

        $this->makeTable('tag', [
            'id' => 'primary',
            'level' => 'integer',
            'name' => 'string',
        ]);

        $this->makeTable('tag_user_map', [
            'id' => 'primary',
            'user_id' => 'integer',
            'tag_id' => 'integer',
            'as' => 'string,nullable',
            'level' => 'int',
        ]);

        $this->makeFK('tag_user_map', 'user_id', 'user', 'id');
        $this->makeFK('tag_user_map', 'tag_id', 'tag', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('tag')->insertMultiple(
            ['name', 'level'],
            [
                ['tag a', 1],
                ['tag b', 2],
                ['tag c', 3],
                ['tag d', 4],
                ['tag e', 5],
                ['tag f', 6],
            ]
        );

        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id', 'level'],
            [
                [1, 1, 1],
                [1, 2, 2],
                [2, 3, 1],

                [1, 4, 3],
                [1, 5, 4],

                [2, 4, 2],
                [2, 6, 3],
            ]
        );

        $this->orm = new ORM(
            new Factory($this->dbal, RelationConfig::getDefault(), null, new LoophpCollectionFactory()),
            new Schema([
                User::class => [
                    Schema::ROLE => 'user',
                    Schema::MAPPER => Mapper::class,
                    Schema::DATABASE => 'default',
                    Schema::TABLE => 'user',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS => ['id', 'email', 'balance'],
                    Schema::TYPECAST => ['id' => 'int', 'balance' => 'float'],
                    Schema::SCHEMA => [],
                    Schema::RELATIONS => [
                        'tags' => [
                            Relation::TYPE => Relation::MANY_TO_MANY,
                            Relation::TARGET => Tag::class,
                            Relation::SCHEMA => [
                                Relation::CASCADE => true,
                                Relation::THROUGH_ENTITY => TagContext::class,
                                Relation::INNER_KEY => 'id',
                                Relation::OUTER_KEY => 'id',
                                Relation::THROUGH_INNER_KEY => 'user_id',
                                Relation::THROUGH_OUTER_KEY => 'tag_id',
                            ],
                        ],
                    ],
                ],
                Tag::class => [
                    Schema::ROLE => 'tag',
                    Schema::MAPPER => Mapper::class,
                    Schema::DATABASE => 'default',
                    Schema::TABLE => 'tag',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS => ['id', 'name', 'level'],
                    Schema::TYPECAST => ['id' => 'int', 'level' => 'int'],
                    Schema::SCHEMA => [],
                    Schema::RELATIONS => [],
                ],
                TagContext::class => [
                    Schema::ROLE => 'tag_context',
                    Schema::MAPPER => Mapper::class,
                    Schema::DATABASE => 'default',
                    Schema::TABLE => 'tag_user_map',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS => ['id', 'user_id', 'tag_id', 'as', 'level'],
                    Schema::TYPECAST => ['id' => 'int', 'user_id' => 'int', 'tag_id' => 'int', 'level' => 'int'],
                    Schema::SCHEMA => [],
                    Schema::RELATIONS => [],
                ],
            ])
        );
    }

    public function testPivotedCollection(): void
    {
        $data = (new Select($this->orm, User::class))->load('tags')->fetchAll();

        $this->assertInstanceOf(LoophpPivotedCollection::class, $data[0]->tags);
        $this->assertInstanceOf(LoophpPivotedCollection::class, $data[1]->tags);

        $this->assertSame(4, $data[0]->tags->count());
        $this->assertSame(3, $data[1]->tags->count());
    }
}
