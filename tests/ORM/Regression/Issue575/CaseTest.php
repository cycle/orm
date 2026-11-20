<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue575;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Select;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

/**
 * Changing the order of a nullable HasMany collection must not detach the children that stay in it.
 */
class CaseTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testReorderKeepsChildren(): void
    {
        $user = $this->fetchUser();
        $user->comments = \array_reverse($user->comments);
        QueryAssert::writes(0, function () use ($user): void {
            $this->save($user);
            $this->assertOwnedComments(['comment 1', 'comment 2', 'comment 3']);
        });
    }

    #[\Testo\Test]
    public function testPrependKeepsChildren(): void
    {
        $user = $this->fetchUser();
        $comment = new Entity\Comment('comment 4');
        $comment->user = $user;
        \array_unshift($user->comments, $comment);
        QueryAssert::writes(1, function () use ($user): void {
            $this->save($user);
            $this->assertOwnedComments(['comment 1', 'comment 2', 'comment 3', 'comment 4']);
        });
    }

    #[\Testo\Test]
    public function testReplaceInTheMiddleDetachesOnlyReplacedChild(): void
    {
        $user = $this->fetchUser();
        $user->comments[1]->user = null;
        $comment = new Entity\Comment('comment 4');
        $comment->user = $user;
        $user->comments[1] = $comment;
        QueryAssert::writes(2, function () use ($user): void {
            $this->save($user);
            $this->assertOwnedComments(['comment 1', 'comment 3', 'comment 4']);
        });
        \Testo\Assert::same($this->getDatabase()->table('comment')
            ->select('content')
            ->where('user_id', null)
            ->fetchAll(\PDO::FETCH_COLUMN), ['comment 2']);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        // Init DB
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function fetchUser(): Entity\User
    {
        $user = (new Select($this->orm, Entity\User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::instanceOf($user, Entity\User::class);
        \Testo\Assert::count($user->comments, 3);

        return $user;
    }

    /**
     * @param list<non-empty-string> $expected Contents of the user's comments, in id order.
     */
    #[\Testo\Common\Attribute\AssertMethod]
    private function assertOwnedComments(array $expected): void
    {
        $user = (new Select($this->orm->withHeap(new Heap()), Entity\User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::same(\array_map(static fn(Entity\Comment $comment): string => $comment->content, $user->comments), $expected);
    }

    private function makeTables(): void
    {
        $this->makeTable(Entity\User::ROLE, [
            'id' => 'primary',
            'login' => 'string',
        ]);

        $this->makeTable(Entity\Comment::ROLE, [
            'id' => 'primary',
            'content' => 'string',
            'user_id' => 'int,nullable',
        ]);
        $this->makeFK(Entity\Comment::ROLE, 'user_id', Entity\User::ROLE, 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('user')->insertMultiple(
            ['login'],
            [
                ['user-1'],
            ],
        );
        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'content'],
            [
                [1, 'comment 1'],
                [1, 'comment 2'],
                [1, 'comment 3'],
            ],
        );
    }
}
