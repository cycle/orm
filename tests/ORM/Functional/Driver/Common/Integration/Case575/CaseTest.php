<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case575;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * Changing the order of a nullable HasMany collection must not detach the children that stay in it.
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testReorderKeepsChildren(): void
    {
        $user = $this->fetchUser();
        $user->comments = \array_reverse($user->comments);

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertOwnedComments(['comment 1', 'comment 2', 'comment 3']);
        $this->assertNumWrites(0);
    }

    public function testPrependKeepsChildren(): void
    {
        $user = $this->fetchUser();
        $comment = new Entity\Comment('comment 4');
        $comment->user = $user;
        \array_unshift($user->comments, $comment);

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertOwnedComments(['comment 1', 'comment 2', 'comment 3', 'comment 4']);
        $this->assertNumWrites(1);
    }

    public function testReplaceInTheMiddleDetachesOnlyReplacedChild(): void
    {
        $user = $this->fetchUser();
        $user->comments[1]->user = null;
        $comment = new Entity\Comment('comment 4');
        $comment->user = $user;
        $user->comments[1] = $comment;

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertOwnedComments(['comment 1', 'comment 3', 'comment 4']);
        $this->assertNumWrites(2);
        $this->assertSame(
            ['comment 2'],
            $this->getDatabase()->table('comment')
                ->select('content')
                ->where('user_id', null)
                ->fetchAll(\PDO::FETCH_COLUMN),
        );
    }

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
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

        $this->assertInstanceOf(Entity\User::class, $user);
        $this->assertCount(3, $user->comments);

        return $user;
    }

    /**
     * @param list<non-empty-string> $expected Contents of the user's comments, in id order.
     */
    private function assertOwnedComments(array $expected): void
    {
        $user = (new Select($this->orm->withHeap(new Heap()), Entity\User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        $this->assertSame(
            $expected,
            \array_map(static fn(Entity\Comment $comment): string => $comment->content, $user->comments),
        );
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
