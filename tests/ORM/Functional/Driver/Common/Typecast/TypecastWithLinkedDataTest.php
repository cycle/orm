<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

use Cycle\ORM\Iterator;
use Cycle\ORM\Mapper\ClasslessMapper;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\Book;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\BookNestedStates;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\BookStates;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\IDCaster;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class TypecastWithLinkedDataTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'created_at' => 'datetime',
        ]);

        $this->makeTable('pivot', [
            'book_id' => 'int,primary',
            'user_id' => 'int,primary',
            'created_at' => 'datetime',
        ]);

        $this->makeTable('book', [
            'id' => 'primary',
            'user_id' => 'int,nullable',
            'states' => 'string',
            'nested_states' => 'string',
            'published_at' => 'datetime',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance', 'created_at'],
            [
                ['hello@world.com', 100, new \DatetimeImmutable('2020-12-20')],
                ['another@world.com', 200, new \DatetimeImmutable('2021-12-21')],
            ]
        );

        $this->getDatabase()->table('book')->insertMultiple(
            ['states', 'nested_states', 'published_at'],
            [
                ['a|b|c', 'a|b|c', new \DatetimeImmutable('2020-11-22')],
                ['x|y|z', 'x|y|z', new \DatetimeImmutable('2021-11-24')],
            ]
        );

        $this->getDatabase()->table('pivot')->insertMultiple(
            ['book_id', 'user_id', 'created_at'],
            [
                [1, 1, new \DatetimeImmutable('2020-12-22')],
                [2, 1, new \DatetimeImmutable('2021-12-24')],
                [3, 1, new \DatetimeImmutable('2021-12-24')],
                [1, 2, new \DatetimeImmutable('2020-12-25')],
                [2, 2, new \DatetimeImmutable('2021-12-26')],
                [3, 2, new \DatetimeImmutable('2021-12-27')],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            'user' => [
                SchemaInterface::ENTITY => User::class,
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'email', 'balance', 'created_at'],
                SchemaInterface::TYPECAST => [
                    'id' => [IDCaster::class, 'wrap'],
                    'balance' => [IDCaster::class, 'wrap'],
                    'created_at' => 'datetime',
                ],
                SchemaInterface::RELATIONS => [
                    'books' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'book',
                        Relation::LOAD => Relation::LOAD_EAGER,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => false,
                            Relation::THROUGH_ENTITY => 'pivot',
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'book_id',
                        ],
                    ],
                    'book' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => 'book',
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            'pivot' => [
                SchemaInterface::MAPPER => ClasslessMapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'pivot',
                SchemaInterface::PRIMARY_KEY => ['book_id', 'user_id'],
                SchemaInterface::COLUMNS => ['book_id', 'user_id', 'created_at'],
                SchemaInterface::TYPECAST => [
                    'book_id' => 'int',
                    'user_id' => 'int',
                    'created_at' => 'datetime',
                ],
                SchemaInterface::RELATIONS => [],
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
        ]));
    }

    // Select

    public function testSelectOne(): void
    {
        $user = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();

        $this->assertNotNull($user->id);
        $this->assertIsNotObject($user->id->value);
        $this->assertEquals(1, $user->id->value);
    }

    /**
     * Test the Typecaster doesn't type casting twice when data passed via links
     */
    public function testSelectMultiple(): void
    {
        $users = (new Select($this->orm, User::class))->orderBy('id', 'asc')->fetchAll();

        $this->assertNotNull($users[0]->id);
        $this->assertIsNotObject($users[0]->id->value);
        $this->assertEquals(1, $users[0]->id->value);
    }

    /**
     * Test the Typecaster doesn't type casting twice when data passed via links
     */
    public function testCustomArrayInIterator(): void
    {
        $mapper = $this->orm->getEntityRegistry()->getMapper('user');
        $bookData = [
            'id' => '1',
            'states' => 'foo|bar',
            'nested_states' => 'foo|bar',
            'published_at' => '2020-12-07',
        ];
        $pivotData = [
            'book_id' => '1',
            'user_id' => '1',
            'created_at' => '2020-12-09',
            '@' => &$bookData,
        ];
        $userData = [
            'email' => 'foo@bar',
            'balance' => '42',
            'created_at' => '2020-12-09',
            'books' => [&$pivotData, &$pivotData, &$pivotData],
            'book' => &$bookData,
        ];

        $data = [&$userData, &$userData, &$userData];

        $iterator = new Iterator($this->orm, User::class, $data, typecast: true);
        /** @var User $user */
        $users = \iterator_to_array($iterator);

        $this->assertCount(3, $users);
    }
}
