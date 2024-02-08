<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\Book;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\Book2;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\BookNestedStates;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\BookStates;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\IDCaster;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\JsonTypecast;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\ParentTypecast;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\UuidTypecast;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\Wrapper;
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

        $this->makeTable('book', [
            'id' => 'primary',
            'user_id' => 'int',
            'states' => 'string',
            'nested_states' => 'string',
            'published_at' => 'datetime',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            'user' => [
                SchemaInterface::ENTITY => User::class,
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'email', 'balance'],
                SchemaInterface::TYPECAST => [
                    'id' => [IDCaster::class, 'wrap'],
                    'balance' => [IDCaster::class, 'wrap'],
                ],
                SchemaInterface::RELATIONS => [
                    'books' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Book::class,
                        Relation::LOAD => Relation::LOAD_EAGER,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => false,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            'book' => [
                SchemaInterface::ENTITY => Book::class,
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'book',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'user_id', 'states', 'nested_states', 'published_at'],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'user_id' => 'int',
                    'states' => [BookStates::class, 'cast'],
                    'nested_states' => [BookNestedStates::class, 'cast'],
                    'published_at' => 'datetime',
                ],
                SchemaInterface::RELATIONS => [],
            ],
            'book2' => [
                SchemaInterface::ENTITY => Book2::class,
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'book',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'title', 'description'],
                SchemaInterface::TYPECAST_HANDLER => [
                    ParentTypecast::class,
                    Typecast::class,
                ],
                SchemaInterface::TYPECAST => [
                    'id' => 'uuid',
                    'title' => ['foo' => 'bar'],
                    'description' => fn () => 'wrong description',
                ],
                SchemaInterface::RELATIONS => [],
            ],
            'book3' => [
                SchemaInterface::ENTITY => Book2::class,
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::PARENT => 'book2',
                SchemaInterface::TABLE => 'book',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'title'],
                SchemaInterface::TYPECAST_HANDLER => [JsonTypecast::class, UuidTypecast::class],
                SchemaInterface::TYPECAST => [
                    'id' => 'uuid',
                    'title' => 'json',
                ],
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
        $this->assertIsNumeric($user->id->value);
    }

    // ORM::make()

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

    public function testCreateFromRawDataWithRelations(): void
    {
        $user = $this->orm->make('user', [
            'id' => '10',
            'email' => 'new@mail.box',
            'balance' => '1000.0',
            'books' => [
                [
                    'id' => '15',
                    'user_id' => '10',
                    'states' => 'foo|bar',
                    'nested_states' => 'buz|fiz',
                    'published_at' => '2012-12-12 12:12:12',
                ],
            ],
        ], typecast: true);
    }

    public function testWrongTypecastShouldBeSkipped(): void
    {
        $book = $this->orm->make('book2', [
            'id' => '15',
            'title' => 'hello world',
            'description' => 'Super long description',
        ], typecast: true);

        $this->assertSame('15', $book->id);
        $this->assertSame('hello world', $book->title);
        $this->assertSame('wrong description', $book->description);
    }

    public function testCompositeTypecastWith(): void
    {
        $book1 = $this->orm->make('book3', [
            'id' => 100,
            'title' => 'Merlin',
        ], typecast: true);

        $book2 = $this->orm->make('book3', [
            'id' => 100,
        ], typecast: true);

        $this->assertSame('uuid', $book1->id);
        $this->assertSame('json', $book1->title);

        $this->assertSame('uuid', $book2->id);
        $this->assertNull($book2->title);
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
