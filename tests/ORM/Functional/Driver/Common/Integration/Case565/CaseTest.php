<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * Lazy-loaded embedded (same-row) relations: end-to-end coverage for `Select::load()`,
 * promise resolution on property access, and `BulkLoader::load()`.
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testSelectLoadEmbedded(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->load('credentials')
            ->wherePK(1)
            ->fetchOne();

        $this->assertInstanceOf(Entity\UserCredentials::class, $user->credentials);
        $this->assertSame('user1', $user->credentials->username);
        $this->assertSame('pass1', $user->credentials->password);
        $this->assertSame(0, $user->credentials->numLogins);
    }

    public function testSelectLoadEmbeddedMultiple(): void
    {
        /** @var array<Entity\User> $users */
        $users = (new Select($this->orm, Entity\User::class))
            ->load('credentials')
            ->orderBy('id')
            ->fetchAll();

        $this->assertCount(2, $users);
        $this->assertSame('user1', $users[0]->credentials->username);
        $this->assertSame('user2', $users[1]->credentials->username);
        $this->assertSame(1, $users[1]->credentials->numLogins);
    }

    public function testSelectWithoutLoadProducesPromise(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();

        $relations = $this->extractEntity($user);
        $this->assertInstanceOf(ReferenceInterface::class, $relations['credentials']);
    }

    public function testPromiseResolvesOnPropertyAccess(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();

        $this->assertInstanceOf(Entity\UserCredentials::class, $user->credentials);
        $this->assertSame('user1', $user->credentials->username);
    }

    public function testBulkLoadLazyEmbeddedSingleEntity(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();

        $rels = $this->extractEntity($user);
        $this->assertInstanceOf(ReferenceInterface::class, $rels['credentials']);

        (new BulkLoader($this->orm))
            ->collect($user)
            ->load('credentials')
            ->run();

        // After BulkLoader the relation is hydrated; reading the fields must not query the DB.
        $this->captureReadQueries();
        $this->assertInstanceOf(Entity\UserCredentials::class, $user->credentials);
        $this->assertSame('user1', $user->credentials->username);
        $this->assertSame('pass1', $user->credentials->password);
        $this->assertSame(0, $user->credentials->numLogins);
        $this->assertNumReads(0);
    }

    public function testBulkLoadLazyEmbeddedMultipleEntities(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var array<Entity\User> $users */
        $users = (new Select($this->orm, Entity\User::class))
            ->orderBy('id')
            ->fetchAll();

        $this->assertCount(2, $users);

        (new BulkLoader($this->orm))
            ->collect(...$users)
            ->load('credentials')
            ->run();

        $this->captureReadQueries();
        $this->assertSame('user1', $users[0]->credentials->username);
        $this->assertSame('user2', $users[1]->credentials->username);
        $this->assertSame(0, $users[0]->credentials->numLogins);
        $this->assertSame(1, $users[1]->credentials->numLogins);
        $this->assertNumReads(0);
    }

    public function testBulkLoadEmbeddedAlongsideHasMany(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var array<Entity\User> $users */
        $users = (new Select($this->orm, Entity\User::class))
            ->orderBy('id')
            ->fetchAll();

        (new BulkLoader($this->orm))
            ->collect(...$users)
            ->load('credentials')
            ->load('comments')
            ->run();

        $this->captureReadQueries();
        $this->assertSame('user1', $users[0]->credentials->username);
        $this->assertSame('user2', $users[1]->credentials->username);
        $this->assertCount(2, $users[0]->comments);
        $this->assertCount(1, $users[1]->comments);
        $this->assertNumReads(0);
    }

    public function testBulkLoadMultipleEmbeddedInSingleQuery(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var array<Entity\User> $users */
        $users = (new Select($this->orm, Entity\User::class))
            ->orderBy('id')
            ->fetchAll();

        // All embedded relations share the parent table, so loading any number of them
        // must collapse into one SQL query.
        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$users)
            ->load('credentials')
            ->load('profile')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        $this->assertSame('user1', $users[0]->credentials->username);
        $this->assertSame('user2', $users[1]->credentials->username);
        $this->assertSame('bio 1', $users[0]->profile->bio);
        $this->assertSame('bio 2', $users[1]->profile->bio);
        $this->assertSame('image1.png', $users[0]->profile->image);
        $this->assertNumReads(0);
    }

    public function testBulkLoadDoesNotRefetchParentFields(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();

        // Mutate the DB row out-of-band: if BulkLoader re-fetched parent columns,
        // $user->email would pick up the new DB value.
        $this->getDatabase()->table('user')->update(['email' => 'changed-in-db@test.com'], ['id' => 1])->run();

        (new BulkLoader($this->orm))
            ->collect($user)
            ->load('credentials')
            ->run();

        $this->assertSame('hello@world.com', $user->email);
        $this->assertInstanceOf(Entity\UserCredentials::class, $user->credentials);
    }

    public function testBulkLoadEmbeddedAcceptsLoadOptionsDTO(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var array<Entity\User> $users */
        $users = (new Select($this->orm, Entity\User::class))
            ->orderBy('id')
            ->fetchAll();

        // The LoadOptions DTO must travel through BulkLoader::load() into the embedded
        // batch Select without exceptions or behavioural regressions.
        (new BulkLoader($this->orm))
            ->collect(...$users)
            ->load('credentials', new LoadOptions(minify: false))
            ->run();

        $this->assertSame('user1', $users[0]->credentials->username);
        $this->assertSame('user2', $users[1]->credentials->username);
    }

    public function testBulkLoadEmbeddedWithCompositePk(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var array<Entity\Order> $orders */
        $orders = (new Select($this->orm, Entity\Order::class))
            ->orderBy('tenant_id')
            ->orderBy('number')
            ->fetchAll();

        $this->assertCount(3, $orders);

        // Composite PK: BulkLoader must build (tenant_id, number) tuples and pass them
        // through Select::wherePK's composite branch.
        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$orders)
            ->load('shipping')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        $this->assertSame('Addr A', $orders[0]->shipping->address);
        $this->assertSame('Alice', $orders[0]->shipping->recipient);
        $this->assertSame('Addr B', $orders[1]->shipping->address);
        $this->assertSame('Addr C', $orders[2]->shipping->address);
        $this->assertNumReads(0);
    }

    public function testBulkLoadDoesNotOverwriteAlreadyLoadedEmbedded(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->load('credentials')
            ->wherePK(1)
            ->fetchOne();

        $originalCredentials = $user->credentials;
        $this->assertInstanceOf(Entity\UserCredentials::class, $originalCredentials);

        // The relation is already a real entity (not a Reference), so BulkLoader must
        // keep the same instance instead of replacing it.
        (new BulkLoader($this->orm))
            ->collect($user)
            ->load('credentials')
            ->run();

        $this->assertSame($originalCredentials, $user->credentials);
        $this->assertSame('user1', $user->credentials->username);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'creds_username' => 'string',
            'creds_password' => 'string',
            'creds_num_logins' => 'int',
            'profile_bio' => 'string',
            'profile_image' => 'string',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer,null',
            'message' => 'string',
        ]);
        $this->makeFK('comment', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('order_table', [
            'tenant_id' => 'int',
            'number' => 'int',
            'total' => 'float',
            'ship_address' => 'string',
            'ship_recipient' => 'string',
        ], pk: ['tenant_id', 'number']);
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance', 'creds_username', 'creds_password', 'creds_num_logins', 'profile_bio', 'profile_image'],
            [
                ['hello@world.com', 100, 'user1', 'pass1', 0, 'bio 1', 'image1.png'],
                ['another@world.com', 200, 'user2', 'pass2', 1, 'bio 2', 'image2.png'],
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
                [2, 'msg 3'],
            ],
        );

        $this->getDatabase()->table('order_table')->insertMultiple(
            ['tenant_id', 'number', 'total', 'ship_address', 'ship_recipient'],
            [
                [10, 1, 99.5, 'Addr A', 'Alice'],
                [10, 2, 50.0, 'Addr B', 'Bob'],
                [20, 1, 12.0, 'Addr C', 'Carol'],
            ],
        );
    }
}
