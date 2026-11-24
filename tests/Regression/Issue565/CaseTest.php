<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue565;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

/**
 * Lazy-loaded embedded (same-row) relations: end-to-end coverage for `Select::load()`,
 * promise resolution on property access, and `BulkLoader::load()`.
 */
final class CaseTest extends OrmTestCase
{
    #[Test]
    public function testSelectLoadEmbedded(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->load('credentials')
            ->wherePK(1)
            ->fetchOne();

        Assert::instanceOf($user->credentials, Entity\UserCredentials::class);
        Assert::same($user->credentials->username, 'user1');
        Assert::same($user->credentials->password, 'pass1');
        Assert::same($user->credentials->numLogins, 0);
    }

    #[Test]
    public function testSelectLoadEmbeddedMultiple(): void
    {
        /** @var array<Entity\User> $users */
        $users = (new Select($this->orm, Entity\User::class))
            ->load('credentials')
            ->orderBy('id')
            ->fetchAll();

        Assert::count($users, 2);
        Assert::same($users[0]->credentials->username, 'user1');
        Assert::same($users[1]->credentials->username, 'user2');
        Assert::same($users[1]->credentials->numLogins, 1);
    }

    #[Test]
    public function testSelectWithoutLoadProducesPromise(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();

        $relations = $this->extractEntity($user);
        Assert::instanceOf($relations['credentials'], ReferenceInterface::class);
    }

    #[Test]
    public function testPromiseResolvesOnPropertyAccess(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();

        Assert::instanceOf($user->credentials, Entity\UserCredentials::class);
        Assert::same($user->credentials->username, 'user1');
    }

    #[Test]
    public function testBulkLoadLazyEmbeddedSingleEntity(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();

        $rels = $this->extractEntity($user);
        Assert::instanceOf($rels['credentials'], ReferenceInterface::class);

        (new BulkLoader($this->orm))
            ->collect($user)
            ->load('credentials')
            ->run();
        // After BulkLoader the relation is hydrated; reading the fields must not query the DB.
        $this->captureReadQueries();
        Assert::instanceOf($user->credentials, Entity\UserCredentials::class);
        Assert::same($user->credentials->username, 'user1');
        Assert::same($user->credentials->password, 'pass1');
        Assert::same($user->credentials->numLogins, 0);
        $this->assertNumReads(0);
    }

    #[Test]
    public function testBulkLoadHydratesEmbeddedReference(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());
        $user = (new Select($this->orm, Entity\User::class))->wherePK(1)->fetchOne();

        (new BulkLoader($this->orm))->collect($user)->load('credentials')->run();

        Assert::instanceOf($this->extractEntity($user)['credentials'], Entity\UserCredentials::class);
    }

    #[Test]
    public function testBulkLoadKeepsLoadedEmbeddedAndHydratesTheRest(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());
        $user = (new Select($this->orm, Entity\User::class))->load('credentials')->wherePK(1)->fetchOne();
        $credentials = $user->credentials;

        (new BulkLoader($this->orm))->collect($user)->load('credentials')->load('profile')->run();

        $relations = $this->extractEntity($user);
        Assert::same($relations['credentials'], $credentials);
        Assert::instanceOf($relations['profile'], Entity\UserProfile::class);
    }

    #[Test]
    public function testBulkLoadLazyEmbeddedMultipleEntities(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var array<Entity\User> $users */
        $users = (new Select($this->orm, Entity\User::class))
            ->orderBy('id')
            ->fetchAll();

        Assert::count($users, 2);

        (new BulkLoader($this->orm))
            ->collect(...$users)
            ->load('credentials')
            ->run();

        $this->captureReadQueries();
        Assert::same($users[0]->credentials->username, 'user1');
        Assert::same($users[1]->credentials->username, 'user2');
        Assert::same($users[0]->credentials->numLogins, 0);
        Assert::same($users[1]->credentials->numLogins, 1);
        $this->assertNumReads(0);
    }

    #[Test]
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
        Assert::same($users[0]->credentials->username, 'user1');
        Assert::same($users[1]->credentials->username, 'user2');
        Assert::count($users[0]->comments, 2);
        Assert::count($users[1]->comments, 1);
        $this->assertNumReads(0);
    }

    #[Test]
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
        Assert::same($users[0]->credentials->username, 'user1');
        Assert::same($users[1]->credentials->username, 'user2');
        Assert::same($users[0]->profile->bio, 'bio 1');
        Assert::same($users[1]->profile->bio, 'bio 2');
        Assert::same($users[0]->profile->image, 'image1.png');
        $this->assertNumReads(0);
    }

    #[Test]
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

        Assert::same($user->email, 'hello@world.com');
        Assert::instanceOf($user->credentials, Entity\UserCredentials::class);
    }

    #[Test]
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

        Assert::same($users[0]->credentials->username, 'user1');
        Assert::same($users[1]->credentials->username, 'user2');
    }

    #[Test]
    public function testBulkLoadEmbeddedWithCompositePk(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());

        /** @var array<Entity\Order> $orders */
        $orders = (new Select($this->orm, Entity\Order::class))
            ->orderBy('tenant_id')
            ->orderBy('number')
            ->fetchAll();

        Assert::count($orders, 3);
        // Composite PK: BulkLoader must build (tenant_id, number) tuples and pass them
        // through Select::wherePK's composite branch.
        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$orders)
            ->load('shipping')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        Assert::same($orders[0]->shipping->address, 'Addr A');
        Assert::same($orders[0]->shipping->recipient, 'Alice');
        Assert::same($orders[1]->shipping->address, 'Addr B');
        Assert::same($orders[2]->shipping->address, 'Addr C');
        $this->assertNumReads(0);
    }

    #[Test]
    public function testBulkLoadDoesNotOverwriteAlreadyLoadedEmbedded(): void
    {
        /** @var Entity\User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->load('credentials')
            ->wherePK(1)
            ->fetchOne();

        $originalCredentials = $user->credentials;
        Assert::instanceOf($originalCredentials, Entity\UserCredentials::class);

        // The relation is already a real entity (not a Reference), so BulkLoader must
        // keep the same instance instead of replacing it.
        (new BulkLoader($this->orm))
            ->collect($user)
            ->load('credentials')
            ->run();

        Assert::same($user->credentials, $originalCredentials);
        Assert::same($user->credentials->username, 'user1');
    }

    #[BeforeTest]
    public function setUp(): void
    {
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
