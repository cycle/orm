<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Select;

use Cycle\Database\Exception\DriverException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CursorTest extends BaseTest
{
    use TableTrait;

    public function testCursorYieldsAllRowsInOrder(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(7);

        $emails = $this->getDatabase()->transaction(function (): array {
            $cursor = (new Select($this->orm, User::class))->orderBy('id')->cursor(3);
            $out = [];
            foreach ($cursor as $user) {
                $out[] = $user->email;
            }
            return $out;
        });

        $this->assertCount(7, $emails);
        $this->assertSame('user-1@example.com', $emails[0]);
        $this->assertSame('user-7@example.com', $emails[6]);
    }

    public function testCursorChunkSmallerThanTotal(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(25);

        $count = $this->getDatabase()->transaction(function (): int {
            $i = 0;
            foreach ((new Select($this->orm, User::class))->orderBy('id')->cursor(7) as $_) {
                $i++;
            }
            return $i;
        });

        $this->assertSame(25, $count);
    }

    public function testCursorChunkExactlyMatchesTotal(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(10);

        $count = $this->getDatabase()->transaction(function (): int {
            $i = 0;
            foreach ((new Select($this->orm, User::class))->orderBy('id')->cursor(10) as $_) {
                $i++;
            }
            return $i;
        });

        $this->assertSame(10, $count);
    }

    public function testCursorOnEmptyTable(): void
    {
        $this->skipUnlessPostgres();

        $items = $this->getDatabase()->transaction(function (): array {
            $out = [];
            foreach ((new Select($this->orm, User::class))->cursor(10) as $u) {
                $out[] = $u;
            }
            return $out;
        });

        $this->assertSame([], $items);
    }

    public function testCursorTypecastsScalars(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(1);

        $this->getDatabase()->transaction(function (): void {
            foreach ((new Select($this->orm, User::class))->cursor(10) as $user) {
                $this->assertIsInt($user->id);
                $this->assertIsFloat($user->balance);
                $this->assertSame(100.0, $user->balance);
            }
        });
    }

    public function testCursorRegistersEntitiesInHeap(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(3);

        $this->getDatabase()->transaction(function (): void {
            $collected = [];
            foreach ((new Select($this->orm, User::class))->orderBy('id')->cursor(2) as $user) {
                $collected[] = $user;
            }

            // Same identity returned from the heap
            foreach ($collected as $entity) {
                $node = $this->orm->getHeap()->get($entity);
                $this->assertNotNull($node);
                $this->assertSame(Node::MANAGED, $node->getStatus());
            }
        });
    }

    public function testCursorReturnsHeapAttachedInstanceForDuplicatePk(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(3);

        // Pre-load user 2 — it's now attached to the heap.
        $pre = (new Select($this->orm, User::class))->wherePK(2)->fetchOne();
        $this->assertNotNull($pre);

        $fromCursor = $this->getDatabase()->transaction(function () {
            $found = null;
            foreach ((new Select($this->orm, User::class))->orderBy('id')->cursor(10) as $user) {
                if ($user->id === 2) {
                    $found = $user;
                    break;
                }
            }
            return $found;
        });

        $this->assertSame($pre, $fromCursor, 'Cursor must reuse the heap-attached instance for duplicate PKs');
    }

    public function testCursorAllowsHeapCleanBetweenChunks(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(20);

        $this->getDatabase()->transaction(function (): void {
            $heap = $this->orm->getHeap();
            $seenInChunk = 0;
            $maxLive = 0;
            $chunk = 5;

            foreach ((new Select($this->orm, User::class))->orderBy('id')->cursor($chunk) as $_) {
                $seenInChunk++;
                $maxLive = \max($maxLive, $this->countHeapEntries($heap, User::class));

                if ($seenInChunk === $chunk) {
                    $heap->clean();
                    $seenInChunk = 0;
                }
            }

            // After heap->clean() in the middle of streaming, the heap never accumulates
            // more than a single chunk worth of entities.
            $this->assertLessThanOrEqual($chunk, $maxLive);
        });
    }

    public function testCursorRequiresActiveTransaction(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(2);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches('/active transaction/i');

        foreach ((new Select($this->orm, User::class))->cursor(10) as $_) {
            // pulling first row triggers DECLARE CURSOR which requires a transaction
        }
    }

    public function testCursorRespectsWhereAndOrderBy(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(10);

        $ids = $this->getDatabase()->transaction(function (): array {
            $cursor = (new Select($this->orm, User::class))
                ->where('balance', '>=', 500.0)
                ->orderBy('id', 'DESC')
                ->cursor(3);
            $out = [];
            foreach ($cursor as $u) {
                $out[] = $u->id;
            }
            return $out;
        });

        // balance = id * 100; balance >= 500 → ids 5..10; DESC → [10,9,8,7,6,5]
        $this->assertSame([10, 9, 8, 7, 6, 5], $ids);
    }

    public function testCursorSupportsEarlyBreak(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(50);

        $result = $this->getDatabase()->transaction(function (): array {
            $seen = 0;
            foreach ((new Select($this->orm, User::class))->orderBy('id')->cursor(5) as $_) {
                if (++$seen === 3) {
                    break;
                }
            }

            // After break, cursor must be closed (finally clause); same transaction continues.
            $total = (new Select($this->orm, User::class))->count();
            return ['seen' => $seen, 'total' => $total];
        });

        $this->assertSame(3, $result['seen']);
        $this->assertSame(50, $result['total']);
    }

    public function testCursorPostloadHasMany(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(3);
        $this->fillCommentsForUsers([1 => 4, 2 => 3, 3 => 0]);

        $byUser = $this->getDatabase()->transaction(function (): array {
            $out = [];
            $cursor = (new Select($this->orm, User::class))
                ->load('comments')
                ->orderBy('id')
                ->cursor(2);
            foreach ($cursor as $user) {
                $out[$user->id] = \array_map(fn($c) => $c->message, \iterator_to_array((function () use ($user) {
                    foreach ($user->comments as $c) {
                        yield $c;
                    }
                })()));
            }
            return $out;
        });

        $this->assertSame(['msg 1-1', 'msg 1-2', 'msg 1-3', 'msg 1-4'], $byUser[1]);
        $this->assertSame(['msg 2-1', 'msg 2-2', 'msg 2-3'], $byUser[2]);
        $this->assertSame([], $byUser[3]);
    }

    public function testCursorInlineHasOne(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(3);
        $this->fillProfileForUsers([1 => 'profile-1.png', 3 => 'profile-3.png']);

        $images = $this->getDatabase()->transaction(function (): array {
            $out = [];
            $cursor = (new Select($this->orm, User::class))
                ->load('profile', ['method' => Select::SINGLE_QUERY])
                ->orderBy('id')
                ->cursor(2);
            foreach ($cursor as $user) {
                $out[$user->id] = $user->profile?->image;
            }
            return $out;
        });

        $this->assertSame(['profile-1.png', null, 'profile-3.png'], \array_values($images));
    }

    public function testCursorInlineBelongsTo(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(2);
        $this->fillCommentsForUsers([1 => 2, 2 => 1]);

        $rows = $this->getDatabase()->transaction(function (): array {
            $out = [];
            $cursor = (new Select($this->orm, Comment::class))
                ->load('user', ['method' => Select::SINGLE_QUERY])
                ->orderBy('id')
                ->cursor(2);
            foreach ($cursor as $comment) {
                $out[] = [$comment->message, $comment->user->id, $comment->user->email];
            }
            return $out;
        });

        $this->assertSame(
            [
                ['msg 1-1', 1, 'user-1@example.com'],
                ['msg 1-2', 1, 'user-1@example.com'],
                ['msg 2-1', 2, 'user-2@example.com'],
            ],
            $rows,
        );
    }

    public function testCursorAllowsWithOnNonMultiplyingRelation(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(2);
        $this->fillCommentsForUsers([1 => 2, 2 => 1]);

        $messages = $this->getDatabase()->transaction(function (): array {
            $cursor = (new Select($this->orm, Comment::class))
                ->with('user')
                ->where('user.id', 2)
                ->orderBy('comment.id')
                ->cursor(10);
            $out = [];
            foreach ($cursor as $comment) {
                $out[] = $comment->message;
            }
            return $out;
        });

        $this->assertSame(['msg 2-1'], $messages);
    }

    public function testCursorInlineHasManySingleQuery(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(3);
        $this->fillCommentsForUsers([1 => 4, 2 => 3, 3 => 0]);

        $byUser = $this->getDatabase()->transaction(function (): array {
            $out = [];
            $cursor = (new Select($this->orm, User::class))
                ->load('comments', ['method' => Select::SINGLE_QUERY])
                ->cursor(10);
            foreach ($cursor as $user) {
                $out[$user->id] = \array_map(fn($c) => $c->message, (function () use ($user) {
                    $r = [];
                    foreach ($user->comments as $c) {
                        $r[] = $c;
                    }
                    return $r;
                })());
            }
            return $out;
        });

        $this->assertSame(['msg 1-1', 'msg 1-2', 'msg 1-3', 'msg 1-4'], $byUser[1]);
        $this->assertSame(['msg 2-1', 'msg 2-2', 'msg 2-3'], $byUser[2]);
        $this->assertSame([], $byUser[3]);
    }

    public function testCursorInlineHasManyAcrossChunkBoundary(): void
    {
        $this->skipUnlessPostgres();
        // 3 parents straddle a chunk boundary; user_2 has more children than fit in one chunk.
        $this->fillUsers(3);
        $this->fillCommentsForUsers([1 => 2, 2 => 10, 3 => 1]);

        $byUser = $this->getDatabase()->transaction(function (): array {
            $out = [];
            $cursor = (new Select($this->orm, User::class))
                ->load('comments', ['method' => Select::SINGLE_QUERY])
                ->cursor(2); // chunk = 2 parents → users [1,2] / [3]
            foreach ($cursor as $user) {
                $msgs = [];
                foreach ($user->comments as $c) {
                    $msgs[] = $c->message;
                }
                $out[$user->id] = $msgs;
            }
            return $out;
        });

        $this->assertCount(2, $byUser[1]);
        $this->assertCount(10, $byUser[2]);
        $this->assertCount(1, $byUser[3]);
        $this->assertSame('msg 2-10', $byUser[2][9]);
    }

    public function testCursorInlineHasManyDoesNotDuplicateParents(): void
    {
        $this->skipUnlessPostgres();
        $this->fillUsers(5);
        $this->fillCommentsForUsers([1 => 3, 2 => 4, 3 => 2, 4 => 5, 5 => 1]);

        $count = $this->getDatabase()->transaction(function (): int {
            $seen = [];
            $cursor = (new Select($this->orm, User::class))
                ->load('comments', ['method' => Select::SINGLE_QUERY])
                ->cursor(2);
            foreach ($cursor as $user) {
                $this->assertArrayNotHasKey(
                    $user->id,
                    $seen,
                    "User id={$user->id} was yielded more than once",
                );
                $seen[$user->id] = true;
            }
            return \count($seen);
        });

        $this->assertSame(5, $count);
    }

    public function testCursorOnNonPostgresThrows(): void
    {
        if (static::DRIVER === 'postgres') {
            $this->markTestSkipped('Postgres supports cursors — covered by other tests.');
        }
        $this->fillUsers(1);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches('/cursors are not supported/i');

        $this->getDatabase()->transaction(function (): void {
            foreach ((new Select($this->orm, User::class))->cursor(10) as $_) {
            }
        });
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'message' => 'string',
        ]);

        $this->makeTable('profile', [
            'id' => 'primary',
            'user_id' => 'integer',
            'image' => 'string',
        ]);

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
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'profile' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Profile::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
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
                Schema::TYPECAST => ['id' => 'int', 'user_id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
            Profile::class => [
                Schema::ROLE => 'profile',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'image'],
                Schema::TYPECAST => ['id' => 'int', 'user_id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    /**
     * MVP: cursor is Postgres-only. On other drivers the underlying DBAL stream() throws,
     * and there is nothing meaningful to assert beyond that — the negative case is already
     * covered by cycle/database's own functional suite. This guard keeps the suite green
     * on sqlite/mysql/sqlserver while still exercising the Postgres path.
     */
    protected function skipUnlessPostgres(): void
    {
        if (static::DRIVER !== 'postgres') {
            $this->markTestSkipped('Cursor mode is only supported on Postgres in the MVP.');
        }
    }

    private function fillCommentsForUsers(array $userIdToCount): void
    {
        $rows = [];
        foreach ($userIdToCount as $userId => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $rows[] = [$userId, "msg {$userId}-{$i}"];
            }
        }
        if ($rows !== []) {
            $this->getDatabase()->table('comment')->insertMultiple(['user_id', 'message'], $rows);
        }
    }

    private function fillProfileForUsers(array $userIdToImage): void
    {
        $rows = [];
        foreach ($userIdToImage as $userId => $image) {
            $rows[] = [$userId, $image];
        }
        if ($rows !== []) {
            $this->getDatabase()->table('profile')->insertMultiple(['user_id', 'image'], $rows);
        }
    }

    private function fillUsers(int $count): void
    {
        // Each row's balance == its primary key * 100.0 (PK is auto-incremented from 1),
        // so balance is aligned with id and tests can assert against ids/balances directly.
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = ["user-{$i}@example.com", $i * 100.0];
        }
        if ($rows !== []) {
            $this->getDatabase()->table('user')->insertMultiple(['email', 'balance'], $rows);
        }
    }

    private function countHeapEntries($heap, string $role): int
    {
        $count = 0;
        foreach ($heap as $_) {
            $count++;
        }
        return $count;
    }
}
