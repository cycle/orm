<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Book;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Employee;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Engineer;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Manager;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Programator;
use Cycle\ORM\Tests\Traits\TableTrait;
use JetBrains\PhpStorm\ArrayShape;

abstract class WithRelationsTest extends BaseTest
{
    use TableTrait;

    protected const
        BOOK_1 = ['id' => 1, 'title' => 'PHP manual'],
        BOOK_2 = ['id' => 2, 'title' => 'Best mentor'],
        BOOK_3 = ['id' => 3, 'title' => 'Wikipedia vol.42'],
        BOOK_4 = ['id' => 4, 'title' => 'How to be Foo when you are Bar'],

        EMPLOYEE_1 = ['id' => 1, 'book_id' => 3, 'name' => 'John', 'age' => 38],
        EMPLOYEE_2 = ['id' => 2, 'book_id' => 2, 'name' => 'Anton', 'age' => 35],
        EMPLOYEE_3 = ['id' => 3, 'book_id' => 1, 'name' => 'Kentarius', 'age' => 27],
        EMPLOYEE_4 = ['id' => 4, 'book_id' => null, 'name' => 'Valeriy', 'age' => 32],

        ENGINEER_2 = ['id' => 2, 'tech_book_id' => 1, 'level' => 8],
        ENGINEER_4 = ['id' => 4, 'tech_book_id' => 4, 'level' => 10],

        PROGRAMATOR_2 = ['id' => 2, 'language' => 'php'],
        PROGRAMATOR_4 = ['id' => 4, 'language' => 'go'],

        MANAGER_1 = ['id' => 1, 'rank' => 'top'],
        MANAGER_3 = ['id' => 3, 'rank' => 'bottom'],

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

        $this->getDatabase()->table('book')->insertMultiple(
            array_keys(self::BOOK_1),
            [
                self::BOOK_1,
                self::BOOK_2,
                self::BOOK_3,
                self::BOOK_4,
            ]
        );
        $this->getDatabase()->table('employee')->insertMultiple(
            array_keys(self::EMPLOYEE_1),
            [
                self::EMPLOYEE_1,
                self::EMPLOYEE_2,
                self::EMPLOYEE_3,
                self::EMPLOYEE_4,
            ]
        );
        $this->getDatabase()->table('engineer')->insertMultiple(
            array_keys(self::ENGINEER_2),
            [
                self::ENGINEER_2,
                self::ENGINEER_4,
            ]
        );
        $this->getDatabase()->table('programator')->insertMultiple(
            array_keys(self::PROGRAMATOR_2),
            [
                self::PROGRAMATOR_2,
                self::PROGRAMATOR_4,
            ]
        );
        $this->getDatabase()->table('manager')->insertMultiple(
            array_keys(self::MANAGER_1),
            [
                self::MANAGER_1,
                self::MANAGER_3,
            ]
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
        $this->logger->display();
    }

    public function testSelectEmployeeAllData(): void
    {
        $selector = new Select($this->orm, Employee::class);

        $this->assertEquals(self::EMPLOYEE_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEmployeeDataFirst(): void
    {
        $selector = (new Select($this->orm, Employee::class))->limit(1);

        $this->assertEquals(self::EMPLOYEE_1_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectEngineerAllData(): void
    {
        $selector = (new Select($this->orm, Engineer::class));

        $this->assertEquals(self::ENGINEER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEngineerDataFirst(): void
    {
        $this->logger->display();

        $selector = (new Select($this->orm, Engineer::class))->limit(1);

        $this->assertEquals(self::ENGINEER_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectProgramatorAllData(): void
    {
        $selector = (new Select($this->orm, Programator::class));

        $this->assertEquals(self::PROGRAMATOR_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectProgramatorDataFirst(): void
    {
        $selector = (new Select($this->orm, Programator::class))->limit(1);

        $this->assertEquals(self::PROGRAMATOR_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectProgramatorWithTechBook(): void
    {
        $selector = (new Select($this->orm, Programator::class))
            ->load('tech_book')
            ->limit(1);

        $this->assertEquals(self::PROGRAMATOR_2_LOADED + ['tech_book' => self::BOOK_1], $selector->fetchData()[0]);
    }

    public function testSelectManagerAllData(): void
    {
        $selector = (new Select($this->orm, Manager::class));

        $this->assertEquals(self::MANAGER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectManagerDataFirst(): void
    {
        $selector = (new Select($this->orm, Manager::class))->limit(1);

        $this->assertEquals(self::MANAGER_1_LOADED, $selector->fetchData()[0]);
    }

    #[ArrayShape([
        Employee::class => "array",
        Programator::class => "array",
        Engineer::class => "array",
        Manager::class => "array",
        Book::class => "array",
    ])]
    private function getSchemaArray(): array
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
                    ]
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
        ];
    }
}
