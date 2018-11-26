<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Collection\PromisedCollection;
use Spiral\ORM\Heap;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\EntityMapper;
use Spiral\ORM\Tests\Fixtures\Tag;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class ManyToManyPromiseTest extends BaseTest
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

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('tag', [
            'id'   => 'primary',
            'name' => 'string'
        ]);

        $this->getDatabase()->table('tag')->insertMultiple(
            ['name'],
            [
                ['tag a'],
                ['tag b'],
                ['tag c'],
            ]
        );

        $this->makeTable('tag_user_map', [
            'user_id' => 'integer',
            'tag_id'  => 'integer'
        ]);

        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id'],
            [
                [1, 1],
                [1, 2],
                [2, 3],
            ]
        );

        $this->orm = $this->orm->withSchema(new Schema([
            User::class => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => EntityMapper::class,
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
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testLoadRelation()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->load('tags');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'tags'    => [
                    [
                        '@pivot' => [
                            'user_id' => 1,
                            'tag_id'  => 1,
                        ],
                        'id'     => 1,
                        'name'   => 'tag a',
                    ],
                    [
                        '@pivot' => [
                            'user_id' => 1,
                            'tag_id'  => 2,
                        ],
                        'id'     => 2,
                        'name'   => 'tag b',
                    ],
                ],
            ],

            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'tags'    => [
                    [
                        '@pivot' => [
                            'user_id' => 2,
                            'tag_id'  => 3,
                        ],
                        'id'     => 3,
                        'name'   => 'tag c',
                    ],
                ],
            ],
        ], $selector->fetchData());
    }

    public function testLoadPromise()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->assertInstanceOf(PromisedCollection::class, $a->tags);
        $this->assertInstanceOf(PromisedCollection::class, $b->tags);

        $this->captureReadQueries();
        $this->assertCount(2, $a->tags);
        $this->assertCount(1, $b->tags);
        $this->assertNumReads(2);
    }

    public function testConsistent()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();

        $this->assertEquals('tag a', $a->tags[0]->name);
        $this->assertEquals('tag b', $a->tags[1]->name);
        $this->assertEquals('tag c', $b->tags[0]->name);

        $this->assertNumReads(2);
    }

    public function testNoWrites()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();

        $this->assertNumReads(0);
        $this->assertNumWrites(0);
    }

    public function testUnlinkManyToManyAndReplaceSome()
    {
        $tagSelector = new Selector($this->orm, Tag::class);

        $selector = new Selector($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->orderBy('id')->fetchAll();

        $a->tags->remove(0);
        $a->tags->add($tagSelector->wherePK(3)->fetchOne());

        // remove all
        $b->tags->clear();
        $t = new Tag();
        $t->name = "new tag";

        $b->tags->add($t);

        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $this->assertSame("tag b", $a->tags[0]->name);
        $this->assertSame("tag c", $a->tags[1]->name);
        $this->assertSame("new tag", $b->tags[0]->name);
    }
}