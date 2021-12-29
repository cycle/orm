<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class ManyToManyScopeTest extends BaseTest
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
            ['user_id', 'tag_id'],
            [
                [1, 1],
                [1, 2],
                [2, 3],

                [1, 4],
                [1, 5],

                [2, 4],
                [2, 6],
            ]
        );
    }

    protected function withTagSchema(array $relationSchema)
    {
        $eSchema = [];
        if (isset($relationSchema[Schema::SCOPE])) {
            $eSchema[Schema::SCOPE] = $relationSchema[Schema::SCOPE];
        }

        $rSchema = $relationSchema[Relation::SCHEMA] ?? [];

        return $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'tags' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                                Relation::CASCADE => true,
                                Relation::THROUGH_ENTITY => TagContext::class,
                                Relation::INNER_KEY => 'id',
                                Relation::OUTER_KEY => 'id',
                                Relation::THROUGH_INNER_KEY => 'user_id',
                                Relation::THROUGH_OUTER_KEY => 'tag_id',
                            ] + $rSchema,
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
                    Schema::SCHEMA => [],
                    Schema::RELATIONS => [],
                ] + $eSchema,
            TagContext::class => [
                Schema::ROLE => 'tag_context',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'tag_id', 'as'],
                Schema::TYPECAST => ['id' => 'int', 'user_id' => 'int', 'tag_id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    public function testWithOrderByPostload(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'DESC']],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::POSTLOAD,
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame('tag e', $a->tags[0]->name);
        $this->assertSame('tag d', $a->tags[1]->name);
        $this->assertSame('tag b', $a->tags[2]->name);
        $this->assertSame('tag a', $a->tags[3]->name);

        $this->assertSame('tag f', $b->tags[0]->name);
        $this->assertSame('tag d', $b->tags[1]->name);
        $this->assertSame('tag c', $b->tags[2]->name);
    }
}
