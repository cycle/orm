<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class RefersToRelationTestRenamedFields extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'         => 'primary',
            'email'      => 'string',
            'balance'    => 'float',
            'last_comment_id_field' => 'integer,nullable'
        ]);

        $this->makeTable('comment', [
            'comment_id' => 'primary',
            'user_id' => 'integer',
            'message' => 'string'
        ], [
            'user_id' => ['table' => 'user', 'column' => 'id']
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance', 'comment_id' => 'last_comment_id_field'],
                Schema::TYPECAST    => ['id' => 'int'],
                Schema::RELATIONS   => [
                    'lastComment' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id'
                        ],
                    ],
                    'comments'    => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],

                ]
            ],
            Comment::class => [
                Schema::ROLE        => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => [
                    'id' => 'comment_id',
                    'user_id',
                    'message',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
        ]));
    }

    public function testCreateUserWithDoubleReference(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->assertNumWrites(3);

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        $this->assertNotNull($u->lastComment);
        $this->assertSame($u->lastComment, $u->comments[0]);
    }

    public function testCreateUserToExistedReference(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->assertNumWrites(3);

        $u2 = new User();
        $u2->email = 'second@email.com';
        $u2->balance = 200;
        $u2->lastComment = $c;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u2);
        $tr->run();
        $this->assertNumWrites(1);

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $u3 = $s->load('lastComment')->load('comments')->wherePK(2)->fetchOne();

        $this->assertNotNull($u3->lastComment);
        $this->assertEquals($u3->lastComment->id, $u->comments[0]->id);
    }

    public function testCreateWhenParentExists(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $c = new Comment();
        $c->message = 'last comment';

        $u->addComment($c);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->assertNumWrites(2);

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        $this->assertNotNull($u->lastComment);
        $this->assertSame($u->lastComment, $u->comments[0]);
    }

    public function testCreateWithoutProperDependency(): void
    {
        $this->expectException(TransactionException::class);

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

        $this->assertNull($u->lastComment);
        $this->assertCount(1, $u->comments);

        $u->lastComment = $u->comments[0];

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $s = new Select($this->orm, User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        $this->assertNotNull($u->lastComment);
        $this->assertCount(1, $u->comments);
        $this->assertSame($u->lastComment, $u->comments[0]);
    }

    public function testSetNull(): void
    {
        $u = new User();
        $u->email = 'email@email.com';
        $u->balance = 100;

        $c = new Comment();
        $c->message = 'last comment';
        $u->addComment($c);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(3);

        $this->orm = $this->orm->withHeap(new Heap());
        $s = new Select($this->orm, User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        $this->assertNotNull($u->lastComment);
        $this->assertCount(1, $u->comments);
        $this->assertSame($u->lastComment, $u->comments[0]);

        $u->lastComment = null;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $s = new Select($this->orm, User::class);

        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        $this->assertNull($u->lastComment);
        $this->assertCount(1, $u->comments);
    }
}
