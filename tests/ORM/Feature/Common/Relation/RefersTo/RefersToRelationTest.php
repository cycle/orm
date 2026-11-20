<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\RefersTo;

use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

class RefersToRelationTest extends OrmTestCase
{
    #[Test]
    public function testCreateUserWithDoubleReference(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        QueryAssert::writes(3, fn() => $this->save($u));

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        Assert::notNull($u->lastComment);
        Assert::same($u->comments[0], $u->lastComment);
    }

    #[Test]
    public function testCreateUserToExistedReference(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        QueryAssert::writes(3, fn() => $this->save($u));

        $u2 = new User();
        $u2->email = 'second@email.com';
        $u2->balance = 200;
        $u2->lastComment = $c;

        QueryAssert::writes(1, fn() => $this->save($u2));

        QueryAssert::writes(0, fn() => $this->save($u2));

        $u3 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('lastComment')->load('comments')->wherePK(2)->fetchOne();

        Assert::notNull($u3->lastComment);
        Assert::equals($u->comments[0]->id, $u3->lastComment->id);
    }

    #[Test]
    public function testCreateWhenParentExists(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $this->save($u);

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        QueryAssert::writes(2, fn() => $this->save($u));

        $u = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('lastComment')
            ->load('comments')
            ->wherePK(1)->fetchOne();

        Assert::notNull($u->lastComment);
        Assert::same($u->comments[0], $u->lastComment);
    }

    #[Test]
    public function testCreateWithoutProperDependency(): void
    {
        Expect::exception(TransactionException::class);

        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';

        $u->lastComment = $c;
        try {
            $tr = new Transaction($this->orm);
            $tr->persist($u);
            $tr->run();
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->orm = $this->orm->withHeap(new Heap());
        }
    }

    #[Test]
    public function testAssignParentAsUpdate(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';
        $u->comments->add($c);

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->orm = $this->orm->withHeap(new Heap());
        $s = new Select($this->orm, User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        Assert::null($u->lastComment);
        Assert::count($u->comments, 1);

        $u->lastComment = $u->comments[0];

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $s = new Select($this->orm, User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        Assert::notNull($u->lastComment);
        Assert::count($u->comments, 1);
        Assert::same($u->comments[0], $u->lastComment);
    }

    #[Test]
    public function testSetNull(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';
        $u->addComment($c);

        QueryAssert::writes(3, fn() => $this->save($u));

        $this->orm->getHeap()->clean();
        $u = (new Select($this->orm, User::class))
            ->load('lastComment')
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        Assert::notNull($u->lastComment);
        Assert::count($u->comments, 1);
        Assert::same($u->comments[0], $u->lastComment);

        $u->lastComment = null;

        QueryAssert::writes(1, fn() => $this->save($u));

        $this->orm->getHeap()->clean();

        $u = (new Select($this->orm, User::class))
            ->load('lastComment')
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        Assert::null($u->lastComment);
        Assert::count($u->comments, 1);
    }

    /**
     * Not nullable RefersTo relation should be transformed to BelongsTo
     */
    #[Test]
    public function testSetNotNullable(): void
    {
        $schema = $this->getSchemaArray();
        $schema[User::class][Schema::RELATIONS]['lastComment'][Relation::SCHEMA][Relation::NULLABLE] = false;
        $this->orm = $this->withSchema(new Schema($schema));

        Assert::instanceOf($this->orm->getRelationMap(User::class)->getRelations()['lastComment'], Relation\BelongsTo::class);
    }

    /**
     * If relation property was unset - ignore this field
     */
    #[Test]
    public function testUnsetProperty(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        $this->save($u);
        $this->orm->getHeap()->clean();

        /** @var User $u */
        $u = (new Select($this->orm, User::class))
            ->load('lastComment')
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        Assert::notNull($u->lastComment);
        unset($u->lastComment);

        QueryAssert::writes(0, fn() => $this->save($u));
    }

    #[Test]
    public function testUpdateRelation(): void
    {
        // Prepare data
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;
        $c1 = new Comment();
        $c1->message = 'last comment';
        $c2 = new Comment();
        $c2->message = 'new last comment';
        $u->addComment($c1);
        $u->addComment($c2);
        $u->lastComment = $c2;
        $this->save($u);
        $this->orm->getHeap()->clean();
        $id = $u->id;
        unset($u, $c1, $c2);

        $user = QueryAssert::reads(1, fn() => (new Select($this->orm, User::class))->wherePK($id)->fetchOne());

        QueryAssert::reads(2, fn() => (new BulkLoader($this->orm))->collect($user)
            ->load('lastComment')
            ->load('comments')->run());
        QueryAssert::reads(0, function () use ($user): void {
            Assert::notBlank($user->comments);
            Assert::notNull($user->lastComment);
        });
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'comment_id' => 'integer,nullable',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'message' => 'string',
        ], [
            'user_id' => ['table' => 'user', 'column' => 'id'],
        ]);

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    private function getSchemaArray(): array
    {
        return [
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance', 'comment_id'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::RELATIONS => [
                    'lastComment' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ];
    }
}
