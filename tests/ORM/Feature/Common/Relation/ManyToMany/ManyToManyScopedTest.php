<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\ManyToMany;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\SortByLevelDESCScope;
use Cycle\ORM\Tests\Fixtures\SortByLevelScope;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class ManyToManyScopedTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testOrderedByScope(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->fetchAll();

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
    public function testOrderedByScopeDESC(): void
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'scope' => new Select\QueryScope([], ['@.level' => 'DESC']),
        ])->fetchAll();

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[3]->name, 'tag a');
        \Testo\Assert::same($a->tags[2]->name, 'tag b');
        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
    public function testScopeInload(): void
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
            'scope' => new Select\QueryScope([], ['@.level' => 'ASC']),
        ])->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
    public function testOrderedDESCInload(): void
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
            'scope' => new Select\QueryScope([], ['@.level' => 'DESC']),
        ])->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[3]->name, 'tag a');
        \Testo\Assert::same($a->tags[2]->name, 'tag b');
        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
    public function testGlobalScope(): void
    {
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
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByLevelScope::class,
            ],
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

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(0);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
    public function testGlobalScopeDESC(): void
    {
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
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByLevelDESCScope::class,
            ],
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

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(0);

        \Testo\Assert::same($a->tags[3]->name, 'tag a');
        \Testo\Assert::same($a->tags[2]->name, 'tag b');
        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
    public function testGlobalScopeDESCButASC(): void
    {
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
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByLevelDESCScope::class,
            ],
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

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'scope' => new SortByLevelScope(),
        ])->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(0);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
    public function testGlobalScopePromised(): void
    {
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
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByLevelScope::class,
            ],
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

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(2);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
    public function testGlobalScopeDESCPromised(): void
    {
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
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByLevelDESCScope::class,
            ],
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

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(2);

        \Testo\Assert::same($a->tags[3]->name, 'tag a');
        \Testo\Assert::same($a->tags[2]->name, 'tag b');
        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Lifecycle\BeforeTest]
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

    protected function withTagSchema(array $tagSchema)
    {
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
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ] + $tagSchema,
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
