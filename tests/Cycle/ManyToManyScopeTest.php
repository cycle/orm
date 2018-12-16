<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Tests\Fixtures\Tag;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;

abstract class ManyToManyScopeTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->makeTable('tag', [
            'id'    => 'primary',
            'level' => 'integer',
            'name'  => 'string'
        ]);

        $this->makeTable('tag_user_map', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'tag_id'  => 'integer',
            'as'      => 'string,nullable'
        ]);

        $this->makeFK('tag_user_map', 'user_id', 'user', 'id');
        $this->makeFK('tag_user_map', 'user_id', 'tag', 'id');

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

    public function testOrderedByScope()
    {
        $this->orm = $this->withTagSchema([
            Relation::SCOPE => new Selector\QueryConstrain([], ['@.level' => 'ASC'])
        ]);

        $selector = new Selector($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag a", $a->tags[0]->name);
        $this->assertSame("tag b", $a->tags[1]->name);
        $this->assertSame("tag d", $a->tags[2]->name);
        $this->assertSame("tag e", $a->tags[3]->name);

        $this->assertSame("tag c", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[2]->name);
    }

    public function testOrderedByScopeDESC()
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Selector($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags', [
            'constrain' => new Selector\QueryConstrain([], ['@.level' => 'DESC'])
        ])->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag a", $a->tags[3]->name);
        $this->assertSame("tag b", $a->tags[2]->name);
        $this->assertSame("tag d", $a->tags[1]->name);
        $this->assertSame("tag e", $a->tags[0]->name);

        $this->assertSame("tag c", $b->tags[2]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[0]->name);
    }

    public function testScopeInload()
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Selector($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags', [
            'method' => Selector\JoinableLoader::INLOAD,
            'constrain'  => new Selector\QueryConstrain([], ['@.level' => 'ASC'])
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag a", $a->tags[0]->name);
        $this->assertSame("tag b", $a->tags[1]->name);
        $this->assertSame("tag d", $a->tags[2]->name);
        $this->assertSame("tag e", $a->tags[3]->name);

        $this->assertSame("tag c", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[2]->name);
    }

    public function testOrderedDESCInload()
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Selector($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags', [
            'method' => Selector\JoinableLoader::INLOAD,
            'constrain'  => new Selector\QueryConstrain([], ['@.level' => 'DESC'])
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag a", $a->tags[3]->name);
        $this->assertSame("tag b", $a->tags[2]->name);
        $this->assertSame("tag d", $a->tags[1]->name);
        $this->assertSame("tag e", $a->tags[0]->name);

        $this->assertSame("tag c", $b->tags[2]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[0]->name);
    }

    public function testScopeViaMapper()
    {
        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'tags' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE           => true,
                            Relation::PIVOT_TABLE       => 'tag_user_map',
                            Relation::PIVOT_DATABASE    => 'default',
                            Relation::PIVOT_COLUMNS     => ['user_id', 'tag_id'],
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THOUGHT_INNER_KEY => 'user_id',
                            Relation::THOUGHT_OUTER_KEY => 'tag_id',
                        ],
                    ]
                ]
            ],
            Tag::class  => [
                Schema::ALIAS       => 'tag',
                Schema::MAPPER      => ManyToManyScopeMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name', 'level'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ]
        ]));

        $selector = new Selector($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);
        $this->assertNumReads(0);

        $this->assertSame("tag e", $a->tags[0]->name);
        $this->assertSame("tag d", $a->tags[1]->name);

        $this->assertSame("tag f", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag c", $b->tags[2]->name);
    }

    public function testScopeViaMapperPromised()
    {
        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'tags' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE           => true,
                            Relation::PIVOT_TABLE       => 'tag_user_map',
                            Relation::PIVOT_DATABASE    => 'default',
                            Relation::PIVOT_COLUMNS     => ['user_id', 'tag_id'],
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THOUGHT_INNER_KEY => 'user_id',
                            Relation::THOUGHT_OUTER_KEY => 'tag_id',
                        ],
                    ]
                ]
            ],
            Tag::class  => [
                Schema::ALIAS       => 'tag',
                Schema::MAPPER      => ManyToManyScopeMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name', 'level'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ]
        ]));

        $selector = new Selector($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->assertFalse($a->tags->getPromise()->__loaded());
        $this->assertSame('tag', $a->tags->getPromise()->__role());
        $this->assertEquals(['id' => 1], $a->tags->getPromise()->__scope());

        $this->captureReadQueries();
        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);
        $this->assertNumReads(2);

        $this->assertTrue($a->tags->getPromise()->__loaded());

        $this->assertSame("tag e", $a->tags[0]->name);
        $this->assertSame("tag d", $a->tags[1]->name);

        $this->assertSame("tag f", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag c", $b->tags[2]->name);
    }


    protected function withTagSchema(array $relationSchema)
    {
        return $this->withSchema(new Schema([
            User::class => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'tags' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::SCHEMA => [
                                Relation::CASCADE           => true,
                                Relation::PIVOT_TABLE       => 'tag_user_map',
                                Relation::PIVOT_DATABASE    => 'default',
                                Relation::PIVOT_COLUMNS     => ['user_id', 'tag_id'],
                                Relation::INNER_KEY         => 'id',
                                Relation::OUTER_KEY         => 'id',
                                Relation::THOUGHT_INNER_KEY => 'user_id',
                                Relation::THOUGHT_OUTER_KEY => 'tag_id',
                            ] + $relationSchema,
                    ]
                ]
            ],
            Tag::class  => [
                Schema::ALIAS       => 'tag',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name', 'level'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ]
        ]));
    }
}

class ManyToManyScopeMapper extends Mapper
{
    public function getConstrain(string $name = self::DEFAULT_CONSTRAIN): ?Selector\ConstrainInterface
    {
        return new Selector\QueryConstrain(['@.level' => ['>=' => 3]], ['@.level' => 'DESC']);
    }
}
