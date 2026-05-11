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
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CursorTest extends BaseTest
{
    use TableTrait;

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

    public function testCursorRejectsLoadedRelation(): void
    {
        $this->skipUnlessPostgres();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/relations/i');

        $this->getDatabase()->transaction(function (): void {
            $cursor = (new Select($this->orm, User::class))
                ->load('comments')
                ->cursor(10);
            foreach ($cursor as $_) {
                // never reached — generator throws on first iteration
            }
        });
    }

    public function testCursorRejectsWithJoinedRelation(): void
    {
        $this->skipUnlessPostgres();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/with\(\)/i');

        $this->getDatabase()->transaction(function (): void {
            $cursor = (new Select($this->orm, User::class))
                ->with('comments')
                ->cursor(10);
            foreach ($cursor as $_) {
            }
        });
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
                Schema::RELATIONS => [],
            ],
        ]));
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
