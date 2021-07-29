<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Book;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Employee;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Engineer;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Manager;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Programator;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Tool;
use Cycle\ORM\Tests\Relation\JTI\Trait\PersistTrait;
use Cycle\ORM\Tests\Relation\JTI\Trait\SelectTrait;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class WithRelationsTest extends JtiBaseTest
{
    use TableTrait;
    use SelectTrait;
    use PersistTrait;

    protected const
        TOOL_1 = ['id' => 1, 'engineer_id' => 2, 'title' => 'Hammer'],
        TOOL_2 = ['id' => 2, 'engineer_id' => 2, 'title' => 'Notebook'],
        TOOL_3 = ['id' => 3, 'engineer_id' => 2, 'title' => 'Notepad'],
        TOOL_4 = ['id' => 4, 'engineer_id' => 2, 'title' => 'IDE'],

        BOOK_1 = ['id' => 1, 'title' => 'PHP manual'],
        BOOK_2 = ['id' => 2, 'title' => 'Best mentor'],
        BOOK_3 = ['id' => 3, 'title' => 'Wikipedia vol.42'],
        BOOK_4 = ['id' => 4, 'title' => 'How to be Foo when you are Bar'],

        EMPLOYEE_1 = parent::EMPLOYEE_1 + ['book_id' => 3],
        EMPLOYEE_2 = parent::EMPLOYEE_2 + ['book_id' => 2],
        EMPLOYEE_3 = parent::EMPLOYEE_3 + ['book_id' => 1],
        EMPLOYEE_4 = parent::EMPLOYEE_4 + ['book_id' => null],

        ENGINEER_2 = parent::ENGINEER_2 + ['tech_book_id' => 1],
        ENGINEER_4 = parent::ENGINEER_4 + ['tech_book_id' => 4],

        EMPLOYEE_1_LOADED = self::EMPLOYEE_1 + ['book' => self::BOOK_3],
        EMPLOYEE_2_LOADED = self::EMPLOYEE_2 + ['book' => self::BOOK_2],
        EMPLOYEE_3_LOADED = self::EMPLOYEE_3 + ['book' => self::BOOK_1],
        EMPLOYEE_4_LOADED = self::EMPLOYEE_4 + ['book' => null],

        ENGINEER_2_LOADED = self::ENGINEER_2 + self::EMPLOYEE_2_LOADED,
        ENGINEER_4_LOADED = self::ENGINEER_4 + self::EMPLOYEE_4_LOADED,

        PROGRAMATOR_2_LOADED = self::PROGRAMATOR_2 + self::ENGINEER_2_LOADED,
        PROGRAMATOR_4_LOADED = self::PROGRAMATOR_4 + self::ENGINEER_4_LOADED,

        MANAGER_1_LOADED = self::MANAGER_1 + self::EMPLOYEE_1_LOADED,
        MANAGER_3_LOADED = self::MANAGER_3 + self::EMPLOYEE_3_LOADED,

        EMPLOYEE_ALL_LOADED = [self::EMPLOYEE_1_LOADED, self::EMPLOYEE_2_LOADED, self::EMPLOYEE_3_LOADED, self::EMPLOYEE_4_LOADED],
        ENGINEER_ALL_LOADED = [self::ENGINEER_2_LOADED, self::ENGINEER_4_LOADED],
        PROGRAMATOR_ALL_LOADED = [self::PROGRAMATOR_2_LOADED, self::PROGRAMATOR_4_LOADED],
        MANAGER_ALL_LOADED = [self::MANAGER_1_LOADED, self::MANAGER_3_LOADED];

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('book', [
            'id'        => 'integer',
            'title'     => 'string',
        ], pk: ['id']);
        $this->makeTable('tool', [
            'id'          => 'integer, primary',
            'engineer_id' => 'integer',
            'title'       => 'string',
        ], pk: ['id']);
        $this->makeTable('employee', [
            'id'      => 'integer',
            'name'    => 'string',
            'age'     => 'integer,nullable',
            'book_id' => 'integer,nullable',
        ], fk: [
            'book_id' => ['table' => 'book', 'column' => 'id']
        ], pk: ['id']);
        $this->makeTable('engineer', [
            'id'           => 'integer',
            'level'        => 'integer',
            'tech_book_id' => 'integer',
        ], fk: [
            'id' => ['table' => 'employee', 'column' => 'id']
        ], pk: ['id']);
        $this->makeTable('programator', [
            'id'        => 'integer',
            'language' => 'string',
        ], fk: [
            'id' => ['table' => 'engineer', 'column' => 'id']
        ], pk: ['id']);
        $this->makeTable('manager', [
            'id'        => 'integer',
            'rank'      => 'string',
        ], fk: [
            'id' => ['table' => 'employee', 'column' => 'id']
        ], pk: ['id']);

        $this->getDatabase()->table('tool')->insertMultiple(
            array_keys(static::TOOL_1),
            [
                self::TOOL_1,
                self::TOOL_2,
                self::TOOL_3,
                self::TOOL_4,
            ]
        );
        $this->getDatabase()->table('book')->insertMultiple(
            array_keys(static::BOOK_1),
            [
                self::BOOK_1,
                self::BOOK_2,
                self::BOOK_3,
                self::BOOK_4,
            ]
        );
        $this->getDatabase()->table('employee')->insertMultiple(
            array_keys(static::EMPLOYEE_1),
            [
                self::EMPLOYEE_1,
                self::EMPLOYEE_2,
                self::EMPLOYEE_3,
                self::EMPLOYEE_4,
            ]
        );
        $this->getDatabase()->table('engineer')->insertMultiple(
            array_keys(static::ENGINEER_2),
            [
                self::ENGINEER_2,
                self::ENGINEER_4,
            ]
        );
        $this->getDatabase()->table('programator')->insertMultiple(
            array_keys(static::PROGRAMATOR_2),
            [
                self::PROGRAMATOR_2,
                self::PROGRAMATOR_4,
            ]
        );
        $this->getDatabase()->table('manager')->insertMultiple(
            array_keys(static::MANAGER_1),
            [
                self::MANAGER_1,
                self::MANAGER_3,
            ]
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
        $this->logger->display();
    }

    /**
     * Parent's relation should be initialized
     */
    public function testLoadProgramatorAndCheckParentsRelations(): void
    {
        /** @var Programator $entity */
        $entity = (new Select($this->orm, Programator::class))->wherePK(2)->fetchOne();

        $this->assertInstanceOf(Book::class, $entity->book);
        $this->assertInstanceOf(Book::class, $entity->tech_book);
    }

    /**
     * Parent's relations should be removed or not removed with their parent
     */
    public function testRemoveProgramatorWithRelations(): void
    {
        /** @var Engineer $engineer */
        $engineer = (new Select($this->orm, Engineer::class))->wherePK(2)->fetchOne();

        $this->captureWriteQueries();
        (new Transaction($this->orm))->delete($engineer)->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        (new Transaction($this->orm))->delete($engineer)->run();
        $this->assertNumWrites(0);

        $this->assertNull((new Select($this->orm, Programator::class))->wherePK(2)->fetchOne());
        $this->assertNull((new Select($this->orm, Engineer::class))->wherePK(2)->fetchOne());
        /** @var Employee $employee */
        $employee = (new Select($this->orm, Employee::class))->wherePK(2)->fetchOne();
        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertInstanceOf(Book::class, $employee->book);
    }

    protected function getSchemaArray(): array
    {
        return [
            Employee::class => [
                Schema::ROLE        => 'employee',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'employee',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name', 'age', 'book_id'],
                Schema::TYPECAST    => ['id' => 'int', 'book_id' => 'int', 'age' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'book' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => 'book',
                        Relation::LOAD   => Relation::LOAD_EAGER,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'book_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ]
                ],
            ],
            Engineer::class => [
                Schema::ROLE        => 'engineer',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'engineer',
                Schema::PARENT      => 'employee',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'level', 'tech_book_id'],
                Schema::TYPECAST    => ['id' => 'int', 'level' => 'int', 'tech_book_id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'tech_book' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => 'book',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'tech_book_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'tools' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => 'tool',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => false,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'engineer_id',
                        ],
                    ],
                ],
            ],
            Programator::class => [
                Schema::ROLE        => 'programator',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'programator',
                Schema::PARENT      => 'engineer',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'language'],
                Schema::TYPECAST    => ['id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
            Manager::class => [
                Schema::ROLE        => 'manager',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'manager',
                Schema::PARENT      => 'employee',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'rank'],
                Schema::TYPECAST    => ['id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
            Book::class => [
                Schema::ROLE        => 'book',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'book',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'title'],
                Schema::TYPECAST    => ['id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
            Tool::class => [
                Schema::ROLE        => 'tool',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tool',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'title', 'engineer_id'],
                Schema::TYPECAST    => ['id' => 'int', 'engineer_id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
        ];
    }
}
