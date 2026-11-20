<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Embedded;

use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class EmbeddedRelationTest extends OrmTestCase
{
    protected const NULLABLE = false;

    #[Test]
    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('credentials');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'credentials' => [
                    'username' => 'user1',
                    'password' => 'pass1',
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'credentials' => [
                    'username' => 'user2',
                    'password' => 'pass2',
                ],
            ],
        ]);
    }

    #[Test]
    public function testInitRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('credentials');

        [$a, $b] = $selector->fetchAll();

        Assert::instanceOf($a->credentials, UserCredentials::class);
        Assert::instanceOf($b->credentials, UserCredentials::class);

        Assert::same($a->credentials->username, 'user1');
        Assert::same($b->credentials->username, 'user2');
    }

    #[Test]
    public function testInitRelationFetchOne(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('credentials')->orderBy('id', 'ASC')->fetchOne();

        Assert::instanceOf($u->credentials, UserCredentials::class);
        Assert::same($u->credentials->username, 'user1');
    }

    #[Test]
    public function testCreateUserWithEmbedded(): void
    {
        $u = new User();
        $u->email = 'new@email.com';
        $u->balance = 900;
        $u->credentials->username = 'user3';
        $u->credentials->password = 'pass3';

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        Assert::same((int) $u->id, 3);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u2 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        Assert::equals($u2->id, $u->id);
        Assert::same($u2->credentials->username, 'user3');
    }

    #[Test]
    public function testNoWrites(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('credentials')->orderBy('id', 'ASC')->fetchOne();

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(0);
    }

    #[Test]
    public function testUpdateEmbeddedValue(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $u->credentials->password = 'newpass';

        // make sure no other fields are updated
        $this->dbal->database()->table('user')->update(
            [
                'balance' => 800,
                'creds_username' => 'altered',
            ],
            [
                'id' => $u->id,
            ],
        )->run();

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u2 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        Assert::equals($u2->id, $u->id);
        Assert::equals($u2->balance, 800);
        Assert::same($u2->credentials->username, 'altered');
        Assert::same($u2->credentials->password, 'newpass');
    }

    #[Test]
    public function testInitRelationReferenceNothing(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(0);
    }

    #[Test]
    public function testUpdateEmbeddedDirectly(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector
            ->orderBy('id', 'ASC')
            ->load('credentials')
            ->fetchOne();

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u->credentials);
        $t->run();
        $this->assertNumWrites(0);

        $u->credentials->username = 'altered';

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u->credentials);
        $t->run();
        $this->assertNumWrites(1);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u2 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        Assert::equals($u2->id, $u->id);
        Assert::same($u2->credentials->username, 'altered');
    }

    #[Test]
    public function testResolvePromise(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        Assert::same($u->credentials->username, 'user1');
    }

    #[Test]
    public function testChangePromise(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $u->credentials->username = 'user3';

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u2 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        Assert::equals($u2->id, $u->id);
        Assert::same($u2->credentials->username, 'user3');
    }

    #[Test]
    public function testSavePromise(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $this->captureWriteQueries();
        $this->captureReadQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(0);
        $this->assertNumReads(0);
    }

    #[Test]
    public function testMovePromise(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $selector = new Select($this->orm, User::class);
        $u2 = $selector->orderBy('id', 'ASC')->wherePK(2)->fetchOne();

        $u2Data = $this->extractEntity($u2);
        $u->credentials = $u2Data['credentials'];

        $this->captureWriteQueries();
        $this->captureReadQueries();
        $this->save($u);
        $this->assertNumWrites(1);
        $this->assertNumReads(1);

        $u3 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('credentials')->wherePK($u->id)->fetchOne();

        Assert::equals($u3->id, $u->id);
        Assert::same($u3->credentials->username, 'user2');

        $u4 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('credentials')->wherePK($u2->id)->fetchOne();

        // unchanged
        Assert::equals($u4->id, $u2->id);
        Assert::same($u4->credentials->username, 'user2');
    }

    #[Test]
    public function testMoveLoadedPromise(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $selector = new Select($this->orm, User::class);
        $u2 = $selector->orderBy('id', 'ASC')->wherePK(2)->fetchOne();

        /** @var Reference $promise */
        $promise = $this->extractEntity($u2)['credentials'];
        $promise->setValue($this->orm->get($promise->getRole(), $promise->getScope(), true));

        $u->credentials = $promise;

        $this->captureWriteQueries();
        $this->captureReadQueries();
        $this->save($u);
        $this->assertNumWrites(1);
        $this->assertNumReads(0);

        $u3 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('credentials')->wherePK($u->id)->fetchOne();

        Assert::equals($u3->id, $u->id);
        Assert::same($u3->credentials->username, 'user2');

        $u4 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('credentials')->wherePK($u2->id)->fetchOne();

        // unchanged
        Assert::equals($u4->id, $u2->id);
        Assert::same($u4->credentials->username, 'user2');
    }

    #[Test]
    public function testMoveClonedEmbedding(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('credentials')->orderBy('id', 'ASC')->fetchOne();

        $selector = new Select($this->orm, User::class);
        $u2 = $selector->load('credentials')->orderBy('id', 'ASC')->wherePK(2)->fetchOne();

        $u->credentials = clone $u2->credentials;

        $this->captureWriteQueries();
        $this->captureReadQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);
        $this->assertNumReads(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u3 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        Assert::equals($u3->id, $u->id);
        Assert::same($u3->credentials->username, 'user2');

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u4 = $selector->load('credentials')->wherePK($u2->id)->fetchOne();

        // unchanged
        Assert::equals($u4->id, $u2->id);
        Assert::same($u4->credentials->username, 'user2');
    }

    #[Test]
    public function testSelectEmbeddable(): void
    {
        $selector = new Select($this->orm, UserCredentials::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        Assert::same($u->username, 'user1');
    }

    #[Test]
    public function testChangeWhole(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $u->credentials = new UserCredentials();
        $u->credentials->username = 'abc';
        $u->credentials->password = 'new-pass';

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u2 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        Assert::equals($u2->id, $u->id);
        Assert::same($u2->credentials->username, 'abc');
        Assert::same($u2->credentials->password, 'new-pass');
    }

    #[Test]
    public function testNullify(): void
    {
        Expect::exception(NullException::class);

        $u = (new Select($this->orm, User::class))
            ->orderBy('id', 'ASC')
            ->fetchOne();

        $u->credentials = null;

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $u2 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('credentials')
            ->wherePK($u->id)
            ->fetchOne();

        Assert::equals($u2->id, $u->id);
        Assert::same($u2->credentials->username, 'user3');
    }

    /**
     * If relation property was unset - ignore this field
     */
    #[Test]
    public function testUnsetProperty(): void
    {
        /** @var User $user */
        $user = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->with('credentials')->fetchOne();

        unset($user->credentials);

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertNumWrites(0);
    }

    // Embedded Relation doesn't support null yet
    //
    // /**
    //  * If relation is replaced with null - delete the child (set user_id to null)
    //  */
    // public function testRemoveChildrenUsingSetNull(): void
    // {
    //     /** @var User $user */
    //     $user = (new Select($this->orm, User::class))
    //         ->wherePK(1)
    //         ->with('credentials')->fetchOne();
    //
    //     $this->assertInstanceOf(UserCredentials::class, $user->credentials);
    //
    //     $this->captureWriteQueries();
    //     $this->save($user);
    //     $this->assertNumWrites(0);
    //
    //     $user->credentials = null;
    //
    //     $this->captureWriteQueries();
    //     $this->save($user);
    //     $this->assertNumWrites(1);
    //
    //     $this->captureWriteQueries();
    //     $this->save($user);
    //     $this->assertNumWrites(0);
    //
    //     $this->orm->getHeap()->clean();
    //     $user = (new Select($this->orm, User::class))
    //         ->wherePK(1)
    //         ->with('credentials')->fetchOne();
    //     $this->assertNull($user->credentials);
    // }
    //
    // public function testUninitializedProperty(): void
    // {
    //     $u = new User();
    //     $u->email = 'many@email.com';
    //     $u->balance = 900;
    //     unset($u->credentials);
    //
    //     $this->captureWriteQueries();
    //     $this->save($u);
    //     $this->assertNumWrites(1);
    //
    //     self::assertFalse(isset($u->credentials));
    //
    //     $u->credentials = null;
    //
    //     $this->captureWriteQueries();
    //     $this->save($u);
    //     $this->assertNumWrites(0);
    // }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'creds_username' => 'string',
            'creds_password' => 'string',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance', 'creds_username', 'creds_password'],
            [
                ['hello@world.com', 100, 'user1', 'pass1'],
                ['another@world.com', 200, 'user2', 'pass2'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'credentials' => [
                        Relation::NULLABLE => self::NULLABLE,
                        Relation::TYPE => Relation::EMBEDDED,
                        Relation::TARGET => 'user:credentials',
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [],
                    ],
                ],
            ],
            UserCredentials::class => [
                Schema::ROLE => 'user:credentials',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => [
                    'id' => 'id',
                    'username' => 'creds_username',
                    'password' => 'creds_password',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
