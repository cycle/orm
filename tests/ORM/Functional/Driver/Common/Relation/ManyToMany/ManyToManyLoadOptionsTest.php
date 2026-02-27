<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\ManyToManyLoadOptions;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class ManyToManyLoadOptionsTest extends BaseTest
{
    use TableTrait;

    public function testLoadWithSingleQueryMethod(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        // Array-based call as reference
        $expected = (new Select($this->orm, User::class))->load('tags', [
            'method' => JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        // DTO-based call
        $res = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(\count($expected), $res);
        $this->assertCount(\count($expected[0]->tags), $res[0]->tags);
        $this->assertCount(\count($expected[1]->tags), $res[1]->tags);

        foreach ($expected[0]->tags as $i => $tag) {
            $this->assertSame($tag->name, $res[0]->tags[$i]->name);
        }
        foreach ($expected[1]->tags as $i => $tag) {
            $this->assertSame($tag->name, $res[1]->tags[$i]->name);
        }
    }

    public function testLoadWithOuterQueryMethod(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'ASC']],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            method: LoadMethod::OuterQuery,
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame('tag a', $a->tags[0]->name);
        $this->assertSame('tag b', $a->tags[1]->name);
        $this->assertSame('tag d', $a->tags[2]->name);
        $this->assertSame('tag e', $a->tags[3]->name);

        $this->assertSame('tag c', $b->tags[0]->name);
        $this->assertSame('tag d', $b->tags[1]->name);
        $this->assertSame('tag f', $b->tags[2]->name);
    }

    public function testLoadWithOrderBy(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'DESC']],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            orderBy: ['@.level' => 'ASC'],
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame('tag a', $a->tags[0]->name);
        $this->assertSame('tag b', $a->tags[1]->name);
        $this->assertSame('tag d', $a->tags[2]->name);
        $this->assertSame('tag e', $a->tags[3]->name);

        $this->assertSame('tag c', $b->tags[0]->name);
        $this->assertSame('tag d', $b->tags[1]->name);
        $this->assertSame('tag f', $b->tags[2]->name);
    }

    public function testLoadWithCustomWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            where: ['@.level' => 1],
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(1, $a->tags);
        $this->assertCount(0, $b->tags);

        $this->assertSame('tag a', $a->tags[0]->name);
    }

    public function testLoadWithCustomWhereAndSingleQuery(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        [$a] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            method: LoadMethod::SingleQuery,
            where: ['@.level' => 1],
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(1, $a->tags);
        $this->assertSame('tag a', $a->tags[0]->name);
    }

    public function testLoadWithScopeDisabled(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => ['>=' => 5]]),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            scope: false,
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);
    }

    public function testLoadWithCustomScope(): void
    {
        $this->orm = $this->withTagSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            scope: new Select\QueryScope(['@.level' => ['>=' => 3]], ['@.level' => 'ASC']),
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame('tag d', $a->tags[0]->name);
        $this->assertSame('tag e', $a->tags[1]->name);

        $this->assertSame('tag c', $b->tags[0]->name);
        $this->assertSame('tag d', $b->tags[1]->name);
        $this->assertSame('tag f', $b->tags[2]->name);
    }

    public function testLoadWithSingleQueryAndScopeAndWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        // Array-based call as reference
        $expected = (new Select($this->orm, User::class))->load('tags', [
            'method' => JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        // DTO-based call
        $res = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(\count($expected), $res);
        $this->assertCount(\count($expected[0]->tags), $res[0]->tags);
        $this->assertCount(\count($expected[1]->tags), $res[1]->tags);

        foreach ($expected[0]->tags as $i => $tag) {
            $this->assertSame($tag->name, $res[0]->tags[$i]->name);
        }
        foreach ($expected[1]->tags as $i => $tag) {
            $this->assertSame($tag->name, $res[1]->tags[$i]->name);
        }
    }

    public function testLoadWithDefaultOptions(): void
    {
        $this->orm = $this->withTagSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions())
            ->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);
    }

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
            ],
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
            ],
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
            ],
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
}
