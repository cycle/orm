<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests;


use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class EmbeddedRelationTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'             => 'primary',
            'email'          => 'string',
            'balance'        => 'float',
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
            User::class            => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'credentials' => [
                        Relation::TYPE   => Relation::EMBEDDED,
                        Relation::TARGET => 'user_credentials',
                        Relation::LOAD   => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [],
                    ],
                ]
            ],
            UserCredentials::class => [
                Schema::ROLE        => 'user_credentials',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => [
                    'id'       => 'id',
                    'username' => 'creds_username',
                    'password' => 'creds_password',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFetchData()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('credentials');

        $this->assertEquals([
            [
                'id'          => 1,
                'email'       => 'hello@world.com',
                'balance'     => 100.0,
                'credentials' => [
                    'username' => 'user1',
                    'password' => 'pass1',
                ]
            ],
            [
                'id'          => 2,
                'email'       => 'another@world.com',
                'balance'     => 200.0,
                'credentials' => [
                    'username' => 'user2',
                    'password' => 'pass2',
                ]
            ]
        ], $selector->fetchData());
    }

    public function testInitRelation()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('credentials');

        list($a, $b) = $selector->fetchAll();

        $this->assertInstanceOf(UserCredentials::class, $a->credentials);
        $this->assertInstanceOf(UserCredentials::class, $b->credentials);

        $this->assertSame('user1', $a->credentials->username);
        $this->assertSame('user2', $b->credentials->username);
    }

    public function testInitRelationFetchOne()
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('credentials')->orderBy('id', 'ASC')->fetchOne();

        $this->assertInstanceOf(UserCredentials::class, $u->credentials);
        $this->assertSame('user1', $u->credentials->username);
    }

    public function testCreateUserWithEmbedded()
    {
        $u = new User();
        $u->email = "new@email.com";
        $u->balance = 900;
        $u->credentials->username = 'user3';
        $u->credentials->password = 'pass3';

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $this->assertSame(3, $u->id);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $u2 = $selector->load('credentials')->wherePK($u->id)->fetchOne();

        $this->assertEquals($u->id, $u2->id);
        $this->assertSame('user3', $u2->credentials->username);
    }

    public function testNoWrites()
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('credentials')->orderBy('id', 'ASC')->fetchOne();

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(0);
    }

    public function testUpdateEmbeddedValue()
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->load('credentials')->orderBy('id', 'ASC')->fetchOne();

        $u->credentials->password = 'newpass';

        // make sure no other fields are updated
        $this->dbal->database()->table('user')->update(
            [
                'balance'        => 800,
                'creds_username' => 'altered',
            ],
            [
                'id' => $u->id
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

    public function testInitRelationReferenceNothing()
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(0);
    }

    public function testUpdateEmbeddedDirectly()
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->load('credentials')->fetchOne();

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

    public function testResolvePromise()
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $this->assertSame('user1', $u->credentials->__resolve()->username);
    }

    public function testChangePromise()
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->orderBy('id', 'ASC')->fetchOne();

        $u->credentials->__resolve()->username = 'user3';

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

    public function testChangeWhole()
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

    /**
     * @expectedException \Cycle\ORM\Exception\Relation\NullException
     */
    public function testNullify()
    {
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