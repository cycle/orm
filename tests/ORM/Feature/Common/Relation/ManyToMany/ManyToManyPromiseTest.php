<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\ManyToMany;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Doctrine\Common\Collections\Collection;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class ManyToManyPromiseTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testLoadRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('tags');

        \Testo\Assert::same($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'tags' => [
                    [
                        'id' => 1,
                        'user_id' => 1,
                        'tag_id' => 1,
                        'as' => 'primary',
                        '@' => [
                            'id' => 1,
                            'name' => 'tag a',
                        ],
                    ],
                    [
                        'id' => 2,
                        'user_id' => 1,
                        'tag_id' => 2,
                        'as' => 'secondary',
                        '@' => [
                            'id' => 2,
                            'name' => 'tag b',
                        ],
                    ],
                ],
            ],

            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'tags' => [
                    [
                        'id' => 3,
                        'user_id' => 2,
                        'tag_id' => 3,
                        'as' => 'primary',
                        '@' => [
                            'id' => 3,
                            'name' => 'tag c',
                        ],
                    ],
                ],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testRelationContextAccess(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->fetchAll();
        QueryAssert::reads(2, function () use ($a, $b): void {
            \Testo\Assert::count($a->tags, 2);
            \Testo\Assert::count($b->tags, 1);
        });
        QueryAssert::reads(0, function () use ($a, $b): void {
            \Testo\Assert::instanceOf($a->tags, \Cycle\ORM\Collection\Pivoted\PivotedCollectionInterface::class);
            \Testo\Assert::instanceOf($b->tags, \Cycle\ORM\Collection\Pivoted\PivotedCollectionInterface::class);
            \Testo\Assert::true($a->tags->hasPivot($a->tags[0]));
            \Testo\Assert::true($a->tags->hasPivot($a->tags[1]));
            \Testo\Assert::true($b->tags->hasPivot($b->tags[0]));
            \Testo\Assert::false($b->tags->hasPivot($a->tags[0]));
            \Testo\Assert::false($b->tags->hasPivot($a->tags[1]));
            \Testo\Assert::false($a->tags->hasPivot($b->tags[0]));
            \Testo\Assert::instanceOf($a->tags->getPivot($a->tags[0]), TagContext::class);
            \Testo\Assert::instanceOf($a->tags->getPivot($a->tags[1]), TagContext::class);
            \Testo\Assert::instanceOf($b->tags->getPivot($b->tags[0]), TagContext::class);
            \Testo\Assert::equals($a->tags->getPivot($a->tags[0])->as, 'primary');
            \Testo\Assert::equals($a->tags->getPivot($a->tags[1])->as, 'secondary');
            \Testo\Assert::equals($b->tags->getPivot($b->tags[0])->as, 'primary');
        });
    }

    #[\Testo\Test]
    public function testNoQueries(): void
    {
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = (new Select($this->orm, User::class))
            ->fetchAll();
        QueryAssert::reads(0, fn() => $this->save($a, $b));
    }

    #[\Testo\Test]
    public function testUnlinkManyToManyAndReplaceSome(): void
    {
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = (new Select($this->orm, User::class))->fetchAll();

        $a->tags->remove(0);

        $a->tags->add((new Select($this->orm, Tag::class))->wherePK(3)->fetchOne());
        $a->tags->getPivot($a->tags[1])->as = 'new';

        // remove all
        $b->tags->clear();

        $t = new Tag();
        $t->name = 'new tag';

        $pc = new TagContext();
        $pc->as = 'super';

        $b->tags->add($t);
        $b->tags->setPivot($t, $pc);
        QueryAssert::writes(6, fn() => $this->save($a, $b));
        QueryAssert::writes(0, fn() => $this->save($a, $b));

        $this->orm = $this->orm->withHeap(new Heap());
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = (new Select($this->orm, User::class))->load('tags')->fetchAll();

        \Testo\Assert::same($a->tags[0]->name, 'tag b');
        \Testo\Assert::same($a->tags->getPivot($a->tags[0])->as, 'new');

        \Testo\Assert::same($b->tags[0]->name, 'new tag');
        \Testo\Assert::same($b->tags->getPivot($b->tags[0])->as, 'super');
    }

    #[\Testo\Test]
    public function testReassign(): void
    {
        $tagSelect = new Select($this->orm, Tag::class);
        $userSelect = new Select($this->orm, User::class);

        /**
         * @var User $user
         */
        $user = $userSelect->load('tags')->fetchOne(['id' => 1]);

        \Testo\Assert::instanceOf($user->tags, Collection::class);
        QueryAssert::writes(0, function () use (&$tr, $user): void {
            $tr = new Transaction($this->orm);
            $tr->persist($user);
            $tr->run();
        });

        $wantTags = ['tag a', 'tag c'];

        foreach ($wantTags as $wantTag) {
            $found = false;

            foreach ($user->tags as $tag) {
                if ($tag->name === $wantTag) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $newTag = $tagSelect->fetchOne(['name' => $wantTag]);
                $user->tags->add($newTag);
            }
        }

        $user->tags = $user->tags->filter(function ($t) use ($wantTags) {
            return in_array($t->name, $wantTags);
        });
        QueryAssert::writes(2, function () use (&$tr, $user): void {
            $tr = new Transaction($this->orm);
            $tr->persist($user);
            $tr->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());

        $user = (new Select($this->orm, User::class))->fetchOne(['id' => 1]);
        \Testo\Assert::count($user->tags, 2);
        \Testo\Assert::same($user->tags[0]->name, 'tag a');
        \Testo\Assert::same($user->tags[1]->name, 'tag c');
    }

    #[\Testo\Test]
    public function testResolvePromise(): void
    {
        /** @var User $u */
        $u = $this->orm->get('user', ['id' => 1]);
        $uData = $this->extractEntity($u);

        \Testo\Assert::instanceOf($uData['tags'], ReferenceInterface::class);
        \Testo\Assert::count($u->tags, 2);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('tags', [
            'id' => 'primary',
            'name' => 'string',
        ]);

        $this->makeTable('tag_user_map', [
            'id' => 'primary',
            'user_id' => 'integer',
            'tag_id' => 'integer',
            'as' => 'string,nullable',
        ]);

        $this->makeFK('tag_user_map', 'user_id', 'user', 'id');
        $this->makeFK('tag_user_map', 'tag_id', 'tags', 'id');
        $this->makeIndex('tag_user_map', ['user_id', 'tag_id'], true);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->getDatabase()->table('tags')->insertMultiple(
            ['name'],
            [
                ['tag a'],
                ['tag b'],
                ['tag c'],
            ],
        );

        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id', 'as'],
            [
                [1, 1, 'primary'],
                [1, 2, 'secondary'],
                [2, 3, 'primary'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
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
                Schema::TABLE => 'tags',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'name'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByIDScope::class,
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
    }
}
