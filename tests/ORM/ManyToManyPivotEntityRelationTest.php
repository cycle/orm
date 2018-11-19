<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Collection\PivotedCollectionInterface;
use Spiral\ORM\Heap;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\EntityMapper;
use Spiral\ORM\Tests\Fixtures\Tag;
use Spiral\ORM\Tests\Fixtures\TagContext;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class ManyToManyPivotEntityRelationTest extends BaseTest
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
            'id'      => 'primary',
            'user_id' => 'integer',
            'tag_id'  => 'integer',
            'as'      => 'string'
        ]);

        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id', 'as'],
            [
                [1, 1, 'primary'],
                [1, 2, 'secondary'],
                [2, 3, 'primary'],
            ]
        );

        $this->orm = $this->orm->withSchema(new Schema([
            User::class       => [
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
                            Relation::PIVOT_ENTITY      => TagContext::class,
                            Relation::PIVOT_COLUMNS     => ['id', 'user_id', 'tag_id', 'as'],
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THOUGHT_INNER_KEY => 'user_id',
                            Relation::THOUGHT_OUTER_KEY => 'tag_id',
                        ],
                    ]
                ]
            ],
            Tag::class        => [
                Schema::ALIAS       => 'tag',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
            TagContext::class => [
                Schema::ALIAS       => 'tag_context',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'tag_id', 'as'],
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
                            'id'      => 1,
                            'user_id' => 1,
                            'tag_id'  => 1,
                            'as'      => 'primary'
                        ],
                        'id'     => 1,
                        'name'   => 'tag a',
                    ],
                    [
                        '@pivot' => [
                            'id'      => 2,
                            'user_id' => 1,
                            'tag_id'  => 2,
                            'as'      => 'secondary'
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
                            'id'      => 3,
                            'user_id' => 2,
                            'tag_id'  => 3,
                            'as'      => 'primary'
                        ],
                        'id'     => 3,
                        'name'   => 'tag c',
                    ],
                ],
            ],
        ], $selector->fetchData());
    }

    public function testRelationContextAccess()
    {
        $selector = new Selector($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $this->assertCount(2, $a->tags);
        $this->assertCount(1, $b->tags);

        $this->assertInstanceOf(PivotedCollectionInterface::class, $a->tags);
        $this->assertInstanceOf(PivotedCollectionInterface::class, $b->tags);

        $this->assertTrue($a->tags->getRelationContext()->has($a->tags[0]));
        $this->assertTrue($a->tags->getRelationContext()->has($a->tags[1]));
        $this->assertTrue($b->tags->getRelationContext()->has($b->tags[0]));

        $this->assertFalse($b->tags->getRelationContext()->has($a->tags[0]));
        $this->assertFalse($b->tags->getRelationContext()->has($a->tags[1]));
        $this->assertFalse($a->tags->getRelationContext()->has($b->tags[0]));

        $this->assertInstanceOf(
            TagContext::class,
            $a->tags->getRelationContext()->get($a->tags[0])
        );

        $this->assertInstanceOf(
            TagContext::class,
            $a->tags->getRelationContext()->get($a->tags[1])
        );

        $this->assertInstanceOf(
            TagContext::class,
            $b->tags->getRelationContext()->get($b->tags[0])
        );

        $this->assertEquals('primary', $a->tags->getRelationContext()->get($a->tags[0])->as);
        $this->assertEquals('secondary', $a->tags->getRelationContext()->get($a->tags[1])->as);
        $this->assertEquals('primary', $b->tags->getRelationContext()->get($b->tags[0])->as);
    }

    public function testCreateWithManyToManyCascadeNoContext()
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

        $this->assertInstanceOf(
            TagContext::class,
            $u->tags->getRelationContext()->get($u->tags[0])
        );
    }

    public function testCreateWithManyToManyPivotContextArray()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $t = new Tag();
        $t->name = "my tag";

        $u->tags->add($t);
        $u->tags->getRelationContext()->set($t, ['as' => 'super']);

        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);

        $this->assertInstanceOf(
            TagContext::class,
            $u->tags->getRelationContext()->get($u->tags[0])
        );

        $this->assertSame('super', $u->tags->getRelationContext()->get($u->tags[0])->as);
    }

    public function testCreateWithManyToManyPivotContext()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $t = new Tag();
        $t->name = "my tag";

        $pc = new TagContext();
        $pc->as = 'super';

        $u->tags->add($t);
        $u->tags->getRelationContext()->set($t, $pc);

        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);

        $this->assertInstanceOf(
            TagContext::class,
            $u->tags->getRelationContext()->get($u->tags[0])
        );

        $this->assertSame('super', $u->tags->getRelationContext()->get($u->tags[0])->as);
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
        $a->tags->getRelationContext()->get($a->tags[1])->as = "new";

        // remove all
        $b->tags->clear();

        $t = new Tag();
        $t->name = "new tag";

        $pc = new TagContext();
        $pc->as = 'super';

        $b->tags->add($t);
        $b->tags->getRelationContext()->set($t, $pc);

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
        $this->assertSame('new', $a->tags->getRelationContext()->get($a->tags[0])->as);

        $this->assertSame("new tag", $b->tags[0]->name);
        $this->assertSame('super', $b->tags->getRelationContext()->get($b->tags[0])->as);
    }
}