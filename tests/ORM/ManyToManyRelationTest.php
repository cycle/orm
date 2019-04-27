<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

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

        $this->makeTable('tag', [
            'id'   => 'primary',
            'name' => 'string'
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
            ['name'],
            [
                ['tag a'],
                ['tag b'],
                ['tag c'],
            ]
        );

        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id', 'as'],
            [
                [1, 1, 'primary'],
                [1, 2, 'secondary'],
                [2, 3, 'primary'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class       => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::TYPECAST    => ['id' => 'int', 'balance' => 'float'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'tags' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE          => true,
                            Relation::THOUGH_ENTITY    => TagContext::class,
                            Relation::INNER_KEY        => 'id',
                            Relation::OUTER_KEY        => 'id',
                            Relation::THOUGH_INNER_KEY => 'user_id',
                            Relation::THOUGH_OUTER_KEY => 'tag_id',
                        ],
                    ]
                ]
            ],
            Tag::class        => [
                Schema::ROLE        => 'tag',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name'],
                Schema::TYPECAST    => ['id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
            TagContext::class => [
                Schema::ROLE        => 'tag_context',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'tag_id', 'as'],
                Schema::TYPECAST    => ['id' => 'int', 'user_id' => 'int', 'tag_id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testLoadRelation()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('tags')->orderBy('id', 'ASC');

        $this->assertSame([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'tags'    => [
                    [
                        'id'      => 1,
                        'user_id' => 1,
                        'tag_id'  => 1,
                        'as'      => 'primary',
                        '@'       => [
                            'id'   => 1,
                            'name' => 'tag a',
                        ],
                    ],
                    [
                        'id'      => 2,
                        'user_id' => 1,
                        'tag_id'  => 2,
                        'as'      => 'secondary',
                        '@'       => [
                            'id'   => 2,
                            'name' => 'tag b',
                        ],
                    ],
                ],
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'tags'    => [
                    [
                        'id'      => 3,
                        'user_id' => 2,
                        'tag_id'  => 3,
                        'as'      => 'primary',
                        '@'       => [
                            'id'   => 3,
                            'name' => 'tag c',
                        ]
                    ],
                ],
            ],
        ], $selector->fetchData());
    }

    public function testLoadRelationInload()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('tags', [
            'method'    => Select\JoinableLoader::INLOAD,
            'constrain' => new Select\QueryConstrain([], ['id' => 'ASC'])
        ])->orderBy(['id' => 'ASC']);

        $this->assertSame([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'tags'    => [
                    [
                        'id'      => 1,
                        'user_id' => 1,
                        'tag_id'  => 1,
                        'as'      => 'primary',
                        '@'       => [
                            'id'   => 1,
                            'name' => 'tag a',
                        ],
                    ],
                    [
                        'id'      => 2,
                        'user_id' => 1,
                        'tag_id'  => 2,
                        'as'      => 'secondary',
                        '@'       => [
                            'id'   => 2,
                            'name' => 'tag b',
                        ],
                    ],
                ],
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'tags'    => [
                    [
                        'id'      => 3,
                        'user_id' => 2,
                        'tag_id'  => 3,
                        'as'      => 'primary',
                        '@'       => [
                            'id'   => 3,
                            'name' => 'tag c',
                        ]
                    ],
                ],
            ],
        ], $selector->fetchData());
    }

    public function testRelationContextAccess()
    {
        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $this->assertCount(2, $a->tags);
        $this->assertCount(1, $b->tags);

        $this->assertInstanceOf(Relation\Pivoted\PivotedCollectionInterface::class, $a->tags);
        $this->assertInstanceOf(Relation\Pivoted\PivotedCollectionInterface::class, $b->tags);

        $this->assertTrue($a->tags->hasPivot($a->tags[0]));
        $this->assertTrue($a->tags->hasPivot($a->tags[1]));
        $this->assertTrue($b->tags->hasPivot($b->tags[0]));

        $this->assertFalse($b->tags->hasPivot($a->tags[0]));
        $this->assertFalse($b->tags->hasPivot($a->tags[1]));
        $this->assertFalse($a->tags->hasPivot($b->tags[0]));

        $this->assertInstanceOf(TagContext::class, $a->tags->getPivot($a->tags[0]));
        $this->assertInstanceOf(TagContext::class, $a->tags->getPivot($a->tags[1]));
        $this->assertInstanceOf(TagContext::class, $b->tags->getPivot($b->tags[0]));

        $this->assertEquals('primary', $a->tags->getPivot($a->tags[0])->as);
        $this->assertEquals('secondary', $a->tags->getPivot($a->tags[1])->as);
        $this->assertEquals('primary', $b->tags->getPivot($b->tags[0])->as);
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
        $tr->persist($u);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);

        $this->assertInstanceOf(TagContext::class, $u->tags->getPivot($u->tags[0]));
    }

    public function testCreateWithManyToManyPivotContextArray()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $t = new Tag();
        $t->name = "my tag";

        $u->tags->add($t);
        $u->tags->setPivot($t, ['as' => 'super']);

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);

        $this->assertInstanceOf(TagContext::class, $u->tags->getPivot($u->tags[0]));
        $this->assertSame('super', $u->tags->getPivot($u->tags[0])->as);
    }

    public function testCreateWithManyToManyNoWrites()
    {
        $u = new User();
        $u->email = "many@email.com";
        $u->balance = 900;

        $t = new Tag();
        $t->name = "my tag";

        $u->tags->add($t);
        $u->tags->setPivot($t, ['as' => 'super']);

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(0);
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
        $u->tags->setPivot($t, $pc);

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u = $selector->load('tags')->wherePK(3)->fetchOne();

        $this->assertSame("many@email.com", $u->email);
        $this->assertCount(1, $u->tags);
        $this->assertSame("my tag", $u->tags[0]->name);

        $this->assertInstanceOf(TagContext::class, $u->tags->getPivot($u->tags[0]));
        $this->assertSame('super', $u->tags->getPivot($u->tags[0])->as);
    }

    public function testUnlinkManyToManyAndReplaceSome()
    {
        $tagSelector = new Select($this->orm, Tag::class);

        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $a->tags->remove(0);
        $a->tags->add($tagSelector->wherePK(3)->fetchOne());
        $a->tags->getPivot($a->tags[1])->as = "new";

        // remove all
        $b->tags->clear();

        $t = new Tag();
        $t->name = "new tag";

        $pc = new TagContext();
        $pc->as = 'super';

        $b->tags->add($t);
        $b->tags->setPivot($t, $pc);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();

        $this->assertNumWrites(6);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();

        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $this->assertSame("tag b", $a->tags[0]->name);
        $this->assertSame('new', $a->tags->getPivot($a->tags[0])->as);

        $this->assertSame("new tag", $b->tags[0]->name);
        $this->assertSame('super', $b->tags->getPivot($b->tags[0])->as);
    }
}