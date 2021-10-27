<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class TypecastTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                SchemaInterface::ROLE => 'user',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'email', 'balance'],
                SchemaInterface::TYPECAST => [
                    'id' => [IDCaster::class, 'wrap'],
                    'balance' => [IDCaster::class, 'wrap'],
                ],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }

    // Insert command

    public function testAIIdTypecastingOnInsert(): void
    {
        $user = new User();
        $user->email = 'Merlin';
        $user->balance = new Wrapper(50);

        $this->save($user);

        $this->assertNotNull($user->id);
        $this->assertIsInt($user->id->value);
    }

    // ORM::make()

    public function testOrmMakeRawDataCastFlag(): void
    {
        $user = $this->orm->make(User::class, [
            'id' => 100,
            'email' => 'Merlin',
            'balance' => 50,
        ], typecast: true);

        $this->assertNotNull($user->id);
        $this->assertIsInt($user->id->value);

        // no exceptions thrown
        $this->save($user);
    }

    public function testOrmMakePreparedDataCastFlag(): void
    {
        $idValue = new Wrapper(100);
        $balanceValue = new Wrapper(50);
        $user = $this->orm->make(User::class, [
            'id' => $idValue,
            'email' => 'Merlin',
            'balance' => $balanceValue,
        ], typecast: false);

        $this->assertSame($idValue, $user->id);
        $this->assertSame($balanceValue, $user->balance);

        // no exceptions thrown
        $this->save($user);
    }

    public function testOrmMakeRehydrateRawDataCastFlag(): void
    {
        $user1 = $this->orm->make(User::class, [
            'id' => 100,
            'email' => 'Merlin',
            'balance' => 50,
        ], typecast: true);

        $user2 = $this->orm->make(User::class, [
            'id' => 100,
            'email' => 'Merlin',
            'balance' => 200,
        ], status: Node::MANAGED, typecast: true);

        $this->assertSame($user1, $user2);
        $this->assertNotNull($user1->id);
        $this->assertSame(200, $user1->balance->value);

        // no exceptions thrown
        $this->save($user1);
    }

    // Select

    public function testSelectOne(): void
    {
        $user = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();

        $this->assertNotNull($user->id);
        $this->assertIsNotObject($user->id->value);
        $this->assertEquals(1, $user->id->value);
    }

    public function testSelectMultiple(): void
    {
        $users = (new Select($this->orm, User::class))->orderBy('id', 'asc')->fetchAll();

        $this->assertNotNull($users[0]->id);
        $this->assertIsNotObject($users[0]->id->value);
        $this->assertEquals(1, $users[0]->id->value);
    }
}

class User
{
    public ?Wrapper $id = null;
    public string $email;
    public Wrapper $balance;
}

class Wrapper
{
    public function __construct(
        public mixed $value
    ) {
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}

class IDCaster
{
    public static function wrap(mixed $value): Wrapper
    {
        return new Wrapper($value);
    }
}
