<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\RefersTo;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class RefersToRelationMiniRowsetTest extends OrmTestCase
{
    #[Test]
    public function testCreateEmptyComment(): void
    {
        $c = new Comment();

        QueryAssert::writes(1, function () use ($c): void {
            $tr = new Transaction($this->orm);
            $tr->persist($c);
            $tr->run();
        });

        $s = new Select($this->orm->withHeap(new Heap()), Comment::class);
        $cd = $s->fetchData();

        Assert::same($cd[0], ['id' => 1]);
    }

    #[Test]
    public function testCreateEmptyCommentMultiple(): void
    {
        $c1 = new Comment();
        $c2 = new Comment();
        $c3 = new Comment();

        QueryAssert::writes(3, function () use ($c1, $c2, $c3): void {
            $tr = new Transaction($this->orm);
            $tr->persist($c1);
            $tr->persist($c2);
            $tr->persist($c3);
            $tr->run();
        });

        $cd = (new Select($this->orm->withHeap(new Heap()), Comment::class))->fetchData();

        Assert::same($cd[0], ['id' => 1]);
        Assert::same($cd[1], ['id' => 2]);
        Assert::same($cd[2], ['id' => 3]);
    }

    #[Test]
    public function testCreateEmptyUser(): void
    {
        $u = new User();

        QueryAssert::writes(1, function () use ($u): void {
            $tr = new Transaction($this->orm);
            $tr->persist($u);
            $tr->run();
        });

        $userData = (new Select($this->orm->withHeap(new Heap()), User::class))->fetchData();

        Assert::same($userData[0], ['id' => 1, 'comment_id' => null]);
    }

    #[Test]
    public function testCreateUserWithComment(): void
    {
        $c = new Comment();
        $u = new User();
        $u->lastComment = $c;

        QueryAssert::writes(2, fn() => $this->save($c, $u));

        $userData = (new Select($this->orm->withHeap(new Heap()), User::class))->fetchData();
        $commentData = (new Select($this->orm->withHeap(new Heap()), Comment::class))->fetchData();

        Assert::same($userData[0], ['id' => 1, 'comment_id' => 1]);
        Assert::same($commentData[0], ['id' => 1]);
    }

    #[BeforeTest]
    public function setUp(): void
    {
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
}
