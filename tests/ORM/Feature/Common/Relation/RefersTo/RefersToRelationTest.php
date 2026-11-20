<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\RefersTo;

use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class RefersToRelationTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testCreateUserWithDoubleReference(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(3);

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        \Testo\Assert::notNull($u->lastComment);
        \Testo\Assert::same($u->comments[0], $u->lastComment);
    }

    #[\Testo\Test]
    public function testCreateUserToExistedReference(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(3);

        $u2 = new User();
        $u2->email = 'second@email.com';
        $u2->balance = 200;
        $u2->lastComment = $c;

        $this->captureWriteQueries();
        $this->save($u2);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($u2);
        $this->assertNumWrites(0);

        $u3 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('lastComment')->load('comments')->wherePK(2)->fetchOne();

        \Testo\Assert::notNull($u3->lastComment);
        \Testo\Assert::equals($u->comments[0]->id, $u3->lastComment->id);
    }

    #[\Testo\Test]
    public function testCreateWhenParentExists(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $this->save($u);

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(2);

        $u = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('lastComment')
            ->load('comments')
            ->wherePK(1)->fetchOne();

        \Testo\Assert::notNull($u->lastComment);
        \Testo\Assert::same($u->comments[0], $u->lastComment);
    }

    #[\Testo\Test]
    public function testCreateWithoutProperDependency(): void
    {
        \Testo\Expect::exception(TransactionException::class);

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

    #[\Testo\Test]
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

        \Testo\Assert::null($u->lastComment);
        \Testo\Assert::count($u->comments, 1);

        $u->lastComment = $u->comments[0];

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $s = new Select($this->orm, User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        \Testo\Assert::notNull($u->lastComment);
        \Testo\Assert::count($u->comments, 1);
        \Testo\Assert::same($u->comments[0], $u->lastComment);
    }

    #[\Testo\Test]
    public function testSetNull(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';
        $u->addComment($c);

        $this->captureWriteQueries();

        $this->save($u);
        $this->assertNumWrites(3);

        $this->orm->getHeap()->clean();
        $u = (new Select($this->orm, User::class))
            ->load('lastComment')
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::notNull($u->lastComment);
        \Testo\Assert::count($u->comments, 1);
        \Testo\Assert::same($u->comments[0], $u->lastComment);

        $u->lastComment = null;

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $this->orm->getHeap()->clean();

        $u = (new Select($this->orm, User::class))
            ->load('lastComment')
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::null($u->lastComment);
        \Testo\Assert::count($u->comments, 1);
    }

    /**
     * Not nullable RefersTo relation should be transformed to BelongsTo
     */
    #[\Testo\Test]
    public function testSetNotNullable(): void
    {
        $schema = $this->getSchemaArray();
        $schema[User::class][Schema::RELATIONS]['lastComment'][Relation::SCHEMA][Relation::NULLABLE] = false;
        $this->orm = $this->withSchema(new Schema($schema));

        \Testo\Assert::instanceOf($this->orm->getRelationMap(User::class)->getRelations()['lastComment'], Relation\BelongsTo::class);
    }

    /**
     * If relation property was unset - ignore this field
     */
    #[\Testo\Test]
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

        \Testo\Assert::notNull($u->lastComment);
        unset($u->lastComment);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);
    }

    #[\Testo\Test]
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

        $this->captureReadQueries();
        /** @var User $user */
        $user = (new Select($this->orm, User::class))->wherePK($id)->fetchOne();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        $this->bulkLoader($user)
            ->load('lastComment')
            ->load('comments')->run();
        $this->assertNumReads(2);

        $this->captureReadQueries();
        $this->assertNotEmpty($user->comments);
        \Testo\Assert::notNull($user->lastComment);
        $this->assertNumReads(0);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

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
