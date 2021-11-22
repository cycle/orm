<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class RefersToRelationMiniRowsetTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'comment_id' => 'integer,nullable',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'comment_id'],
                Schema::TYPECAST => ['id' => 'int', 'comment_id' => 'int'],
                Schema::RELATIONS => [
                    'lastComment' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id',
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
                Schema::COLUMNS => ['id'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    public function testCreateEmptyComment(): void
    {
        $c = new Comment();

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($c);
        $tr->run();

        $this->assertNumWrites(1);

        $s = new Select($this->orm->withHeap(new Heap()), Comment::class);
        $cd = $s->fetchData();

        $this->assertSame(['id' => 1], $cd[0]);
    }

    public function testCreateEmptyCommentMultiple(): void
    {
        $c1 = new Comment();
        $c2 = new Comment();
        $c3 = new Comment();

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($c1);
        $tr->persist($c2);
        $tr->persist($c3);
        $tr->run();

        $this->assertNumWrites(3);

        $cd = (new Select($this->orm->withHeap(new Heap()), Comment::class))->fetchData();

        $this->assertSame(['id' => 1], $cd[0]);
        $this->assertSame(['id' => 2], $cd[1]);
        $this->assertSame(['id' => 3], $cd[2]);
    }

    public function testCreateEmptyUser(): void
    {
        $u = new User();

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->assertNumWrites(1);

        $userData = (new Select($this->orm->withHeap(new Heap()), User::class))->fetchData();

        $this->assertSame(['id' => 1, 'comment_id' => null], $userData[0]);
    }

    public function testCreateUserWithComment(): void
    {
        $c = new Comment();
        $u = new User();
        $u->lastComment = $c;

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($c);
        $tr->persist($u);
        $tr->run();

        $this->assertNumWrites(3);

        $userData = (new Select($this->orm->withHeap(new Heap()), User::class))->fetchData();
        $commentData = (new Select($this->orm->withHeap(new Heap()), Comment::class))->fetchData();

        $this->assertSame(['id' => 1, 'comment_id' => 1], $userData[0]);
        $this->assertSame(['id' => 1], $commentData[0]);
    }
}
