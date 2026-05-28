<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Mapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\NotDeletedScope;
use Cycle\ORM\Tests\Fixtures\NotDeletedWrappedScope;
use Cycle\ORM\Tests\Fixtures\SoftDeletedMapper;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class SoftDeletesTest extends BaseTest
{
    use TableTrait;

    public function testCreate(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $data = $s->fetchData();

        $this->assertNull($data[0]['deleted_at']);
    }

    public function testDelete(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $orm = $this->orm->withHeap(new Heap());
        $u = (new Select($orm, User::class))->fetchOne();

        (new Transaction($orm))->delete($u)->run();

        // must be deleted
        $orm = $this->orm->withHeap(new Heap());
        $s = $orm->getRepository(User::class);
        $this->assertNull($s->findOne());

        $this->assertSame($s, $orm->getRepository(User::class));

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $s->scope(new NotDeletedScope());
        $this->assertNull($s->fetchOne());

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $s->scope(null);
        $this->assertNotNull($s->fetchOne());
    }

    /**
     * Demonstrates the historic problem: a plain scope that adds a top-level
     * WHERE is bypassed by a user-added orWhere due to AND-over-OR precedence.
     */
    public function testScopeBypassedByOrWhereWithoutWrapWhere(): void
    {
        $this->seedAliceDeletedAndBobAlive();

        // No wrapWhere here — the scope contributes a top-level AND that ORs reach over.
        $orm = $this->orm->withHeap(new Heap());
        $rows = (new Select($orm, User::class))
            ->scope(new NotDeletedScope())
            ->where('id', 1)
            ->orWhere('id', 2)
            ->fetchAll();

        // SQL: WHERE {id} = ? OR {id} = ? AND {deleted_at} IS NULL
        //    ≡ WHERE {id} = 1 OR ({id} = 2 AND {deleted_at} IS NULL)
        // Alice (id=1) is returned despite being soft-deleted — scope is bypassed.
        $this->assertCount(2, $rows);
        $emails = \array_map(static fn($u) => $u->email, $rows);
        \sort($emails);
        $this->assertSame(['alice@test.com', 'bob@test.com'], $emails);
    }

    /**
     * Same query as above but the scope calls wrapWhere() before adding its
     * condition. The user OR is enclosed in a group, the scope stays effective.
     */
    public function testScopeProtectedByWrapWhereAgainstOrWhere(): void
    {
        $this->seedAliceDeletedAndBobAlive();

        $orm = $this->orm->withHeap(new Heap());
        $rows = (new Select($orm, User::class))
            ->scope(new NotDeletedWrappedScope())
            ->where('id', 1)
            ->orWhere('id', 2)
            ->fetchAll();

        // SQL: WHERE ({id} = ? OR {id} = ?) AND {deleted_at} IS NULL
        // Only Bob (id=2, alive) survives the scope.
        $this->assertCount(1, $rows);
        $this->assertSame('bob@test.com', $rows[0]->email);
    }

    /**
     * Wrap-aware scope must remain a no-op when the user supplies no WHERE at all —
     * wrapWhere() short-circuits on empty token state, scope's own condition is the
     * only thing left.
     */
    public function testWrappedScopeWithNoUserWhere(): void
    {
        $this->seedAliceDeletedAndBobAlive();

        $orm = $this->orm->withHeap(new Heap());
        $rows = (new Select($orm, User::class))
            ->scope(new NotDeletedWrappedScope())
            ->fetchAll();

        // SQL: WHERE {deleted_at} IS NULL
        $this->assertCount(1, $rows);
        $this->assertSame('bob@test.com', $rows[0]->email);
    }

    private function seedAliceDeletedAndBobAlive(): void
    {
        $alice = new User();
        $alice->email = 'alice@test.com';
        $alice->balance = 100;

        $bob = new User();
        $bob->email = 'bob@test.com';
        $bob->balance = 200;

        (new Transaction($this->orm))->persist($alice)->persist($bob)->run();

        // Soft-delete Alice; Bob stays alive.
        $orm = $this->orm->withHeap(new Heap());
        $alice = (new Select($orm, User::class))->wherePK(1)->fetchOne();
        (new Transaction($orm))->delete($alice)->run();
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'deleted_at' => 'datetime,null',
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => SoftDeletedMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance', 'deleted_at'],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'balance' => 'float',
                    'deleted_at' => 'datetime',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => NotDeletedScope::class,
            ],
        ]));
    }
}
