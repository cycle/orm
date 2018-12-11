<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Doctrine\Common\Collections\Collection;
use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Selector\JoinableLoader;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Tests\Fixtures\Tag;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

abstract class ManyToManyRelationTest extends BaseTest
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
                Schema::MAPPER      => Mapper::class,
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

    public function testLoadRelationInload()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->load('tags', ['method' => JoinableLoader::INLOAD]);

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

    public function testRelationAccess()
    {
        $selector = new Selector($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $this->assertCount(2, $a->tags);
        $this->assertCount(1, $b->tags);

        $this->assertInstanceOf(Collection::class, $a->tags);
        $this->assertInstanceOf(Collection::class, $b->tags);

        $this->assertSame("tag a", $a->tags[0]->name);
        $this->assertSame("tag b", $a->tags[1]->name);
        $this->assertSame("tag c", $b->tags[0]->name);
    }

    public function testCreateWithManyToManyCascade()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $t = new Tag();
        $t->name = "my tag";

        $u->tags->add($t);

        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);
    }

    public function testNoWriteOperations()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $t = new Tag();
        $t->name = "my tag";

        $u->tags->add($t);

        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testCreateWithManyToMany()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $t = new Tag();
        $t->name = "my tag";

        $u->tags->add($t);

        $tr = new Transaction($this->orm);
        $tr->store($t);
        $tr->store($u);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);
    }

    public function testCreateWithManyToManyStoreTagAfterUser()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $t = new Tag();
        $t->name = "my tag";

        $u->tags->add($t);

        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->store($t);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);
    }

    public function testCreateWithManyToManyMultilink()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $u2 = new User();
        $u2->email = "many2@email.com";
        $u2->balance = 1900;

        $t = new Tag();
        $t->name = "my tag";

        $u->tags->add($t);
        $u2->tags->add($t);

        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->store($u2);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(4)->fetchOne();

        $this->assertSame("many2@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);
    }

    public function testUnlinkManyToManyAndReplaceSome()
    {
        $tagSelector = new Selector($this->orm, Tag::class);

        $selector = new Selector($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

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