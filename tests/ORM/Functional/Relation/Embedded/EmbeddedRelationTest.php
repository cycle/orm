<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Relation\Embedded;

use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\BaseTest;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class EmbeddedRelationTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

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
            ]
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

    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('credentials');

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

    public function testInitRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('credentials');

        [$a, $b] = $selector->fetchAll();

        $this->assertInstanceOf(UserCredentials::class, $a->credentials);
        $this->assertInstanceOf(UserCredentials::class, $b->credentials);

        $this->assertSame('user1', $a->credentials->username);
        $this->assertSame('user2', $b->credentials->username);
    }

    public function testInitRelationFetchOne(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('credentials')->orderBy('id', 'ASC')->fetchOne();

        $this->assertInstanceOf(UserCredentials::class, $u->credentials);
        $this->assertSame('user1', $u->credentials->username);
    }

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

        $this->assertSame(3, $u->id);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u2 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        $this->assertEquals($u->id, $u2->id);
        $this->assertSame('user3', $u2->credentials->username);
    }

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

    public function testUpdateEmbeddedValue(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('credentials')->orderBy('id', 'ASC')->fetchOne();

        $u->credentials->password = 'newpass';

        // make sure no other fields are updated
        $this->dbal->database()->table('user')->update(
            [
                'balance' => 800,
                'creds_username' => 'altered',
            ],
            [
                'id' => $u->id,
            ]
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

        $this->assertEquals($u->id, $u2->id);
        $this->assertEquals(800, $u2->balance);
        $this->assertSame('altered', $u2->credentials->username);
        $this->assertSame('newpass', $u2->credentials->password);
    }

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

        $this->assertEquals($u->id, $u2->id);
        $this->assertSame('altered', $u2->credentials->username);
    }

    public function testResolvePromise(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $this->assertSame('user1', $u->credentials->username);
    }

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

        $this->assertEquals($u->id, $u2->id);
        $this->assertSame('user3', $u2->credentials->username);
    }

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

        $this->assertEquals($u->id, $u3->id);
        $this->assertSame('user2', $u3->credentials->username);

        $u4 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('credentials')->wherePK($u2->id)->fetchOne();

        // unchanged
        $this->assertEquals($u2->id, $u4->id);
        $this->assertSame('user2', $u4->credentials->username);
    }

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

        $this->assertEquals($u->id, $u3->id);
        $this->assertSame('user2', $u3->credentials->username);

        $u4 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('credentials')->wherePK($u2->id)->fetchOne();

        // unchanged
        $this->assertEquals($u2->id, $u4->id);
        $this->assertSame('user2', $u4->credentials->username);
    }

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

        $this->assertEquals($u->id, $u3->id);
        $this->assertSame('user2', $u3->credentials->username);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u4 = $selector->load('credentials')->wherePK($u2->id)->fetchOne();

        // unchanged
        $this->assertEquals($u2->id, $u4->id);
        $this->assertSame('user2', $u4->credentials->username);
    }

    public function testSelectEmbeddable(): void
    {
        $selector = new Select($this->orm, UserCredentials::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $this->assertSame('user1', $u->username);
    }

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

        $this->assertEquals($u->id, $u2->id);
        $this->assertSame('abc', $u2->credentials->username);
        $this->assertSame('new-pass', $u2->credentials->password);
    }

    public function testNullify(): void
    {
        $this->expectException(NullException::class);

        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $u->credentials = null;

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u2 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        $this->assertEquals($u->id, $u2->id);
        $this->assertSame('user3', $u2->credentials->username);
    }
}
