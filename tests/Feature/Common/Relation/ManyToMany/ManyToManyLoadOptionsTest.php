<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\ManyToMany;

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
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class ManyToManyLoadOptionsTest extends OrmTestCase
{
    #[Test]
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

        Assert::count($res, \count($expected));
        Assert::count($res[0]->tags, \count($expected[0]->tags));
        Assert::count($res[1]->tags, \count($expected[1]->tags));

        foreach ($expected[0]->tags as $i => $tag) {
            Assert::same($res[0]->tags[$i]->name, $tag->name);
        }
        foreach ($expected[1]->tags as $i => $tag) {
            Assert::same($res[1]->tags[$i]->name, $tag->name);
        }
    }

    #[Test]
    public function testLoadWithOuterQueryMethod(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'ASC']],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            method: LoadMethod::OuterQuery,
        ))->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testLoadWithOrderBy(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'DESC']],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            orderBy: ['@.level' => 'ASC'],
        ))->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testLoadWithCustomWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            where: ['@.level' => 1],
        ))->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 1);
        Assert::count($b->tags, 0);

        Assert::same($a->tags[0]->name, 'tag a');
    }

    #[Test]
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

        Assert::count($a->tags, 1);
        Assert::same($a->tags[0]->name, 'tag a');
    }

    #[Test]
    public function testLoadWithScopeDisabled(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => ['>=' => 5]]),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            scope: false,
        ))->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);
    }

    #[Test]
    public function testLoadWithCustomScope(): void
    {
        $this->orm = $this->withTagSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            scope: new Select\QueryScope(['@.level' => ['>=' => 3]], ['@.level' => 'ASC']),
        ))->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 2);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag d');
        Assert::same($a->tags[1]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
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

        Assert::count($res, \count($expected));
        Assert::count($res[0]->tags, \count($expected[0]->tags));
        Assert::count($res[1]->tags, \count($expected[1]->tags));

        foreach ($expected[0]->tags as $i => $tag) {
            Assert::same($res[0]->tags[$i]->name, $tag->name);
        }
        foreach ($expected[1]->tags as $i => $tag) {
            Assert::same($res[1]->tags[$i]->name, $tag->name);
        }
    }

    #[Test]
    public function testLoadWithDefaultOptions(): void
    {
        $this->orm = $this->withTagSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions())
            ->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);
    }

    #[Test]
    public function loadUsingAJoinedRelationReadsItsColumns(): void
    {
        $this->orm = $this->withTagSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))
            ->with('tags', ['as' => 'picked', 'where' => ['@.level' => ['>=' => 5]]])
            ->load('tags', ['using' => 'picked', 'pivot' => ['using' => 'picked_pivot']])
            ->orderBy('user.id')
            ->fetchAll();

        Assert::same($this->tagNames($a), ['tag e']);
        Assert::same($this->tagNames($b), ['tag f']);
    }

    #[Test]
    public function testNullOptionsKeepRelationWhereAndOrderBy(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [
                Relation::WHERE => ['@.level' => ['>=' => 3]],
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))
            ->load('tags', ['where' => null, 'orderBy' => null])
            ->orderBy('user.id')
            ->fetchAll();

        Assert::same($this->tagNames($a), ['tag e', 'tag d']);
        Assert::same($this->tagNames($b), ['tag f', 'tag d', 'tag c']);
    }

    #[Test]
    public function testRelationWhereInSingleQueryKeepsParentsWithoutMatches(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => 5]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))
            ->load('tags', ['method' => JoinableLoader::INLOAD])
            ->orderBy('user.id')
            ->fetchAll();

        Assert::same($this->tagNames($a), ['tag e']);
        Assert::same($this->tagNames($b), []);
    }

    #[Test]
    public function testPivotWhereFiltersRelatedEntities(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [
                Relation::THROUGH_WHERE => ['@.tag_id' => ['>' => 3]],
                Relation::ORDER_BY => ['@.level' => 'ASC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags')->orderBy('user.id')->fetchAll();

        Assert::same($this->tagNames($a), ['tag d', 'tag e']);
        Assert::same($this->tagNames($b), ['tag d', 'tag f']);
    }

    #[Test]
    public function testPivotWhereInSingleQueryKeepsParentsWithoutMatches(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::THROUGH_WHERE => ['@.tag_id' => 5]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))
            ->load('tags', ['method' => JoinableLoader::INLOAD])
            ->orderBy('user.id')
            ->fetchAll();

        Assert::same($this->tagNames($a), ['tag e']);
        Assert::same($this->tagNames($b), []);
    }

    #[Test]
    public function testPivotCanBeReconfiguredByPath(): void
    {
        $this->orm = $this->withTagSchema([]);

        $users = (new Select($this->orm, User::class))
            ->with('tags')
            ->with('tags.@', ['as' => 'tm'])
            ->where('tm.tag_id', 6)
            ->fetchAll();

        Assert::count($users, 1);
        Assert::same($users[0]->email, 'another@world.com');
    }

    #[Test]
    public function testJoinedRelationAllowsLimit(): void
    {
        $this->orm = $this->withTagSchema([]);

        $users = (new Select($this->orm, User::class))
            ->with('tags')
            ->where('tags.level', 6)
            ->limit(1)
            ->fetchAll();

        Assert::count($users, 1);
        Assert::same($users[0]->email, 'another@world.com');
    }

    #[Test]
    public function testLoadFromArchiveTable(): void
    {
        $this->makeTable('tag_archive', [
            'id' => 'primary',
            'level' => 'integer',
            'name' => 'string',
        ]);

        $this->getDatabase()->table('tag_archive')->insertMultiple(
            ['name', 'level'],
            [
                ['archived a', 1],
                ['archived b', 2],
                ['archived c', 3],
                ['archived d', 4],
                ['archived e', 5],
                ['archived f', 6],
            ],
        );

        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'ASC']],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('tags', new ManyToManyLoadOptions(
            table: 'tag_archive',
        ))->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'archived a');
        Assert::same($a->tags[1]->name, 'archived b');
        Assert::same($a->tags[2]->name, 'archived d');
        Assert::same($a->tags[3]->name, 'archived e');

        Assert::same($b->tags[0]->name, 'archived c');
        Assert::same($b->tags[1]->name, 'archived d');
        Assert::same($b->tags[2]->name, 'archived f');
    }

    #[BeforeTest]
    public function setUp(): void
    {
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

    /**
     * @return list<string>
     */
    private function tagNames(User $user): array
    {
        return \array_map(static fn(Tag $tag): string => $tag->name, [...$user->tags]);
    }
}
