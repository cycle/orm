<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Employee;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Engineer;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Manager;
use Cycle\ORM\Tests\Traits\TableTrait;
use JetBrains\PhpStorm\ArrayShape;

abstract class SelectTest extends BaseTest
{
    use TableTrait;

    protected const
        EMPLOYEE_1 = ['id' => 1, 'name' => 'John', 'age' => 38],
        EMPLOYEE_2 = ['id' => 2, 'name' => 'Anton', 'age' => 35],
        EMPLOYEE_3 = ['id' => 3, 'name' => 'Kentarius', 'age' => 27],
        EMPLOYEE_4 = ['id' => 4, 'name' => 'Valeriy', 'age' => 32],

        ENGINEER_2 = ['id' => 2, 'level' => 8],
        ENGINEER_4 = ['id' => 4, 'level' => 10],

        MANAGER_1 = ['id' => 1, 'rank' => 'top'],
        MANAGER_3 = ['id' => 3, 'rank' => 'bottom'],

        ENGINEER_2_LOADED = self::ENGINEER_2 + self::EMPLOYEE_2,
        ENGINEER_4_LOADED = self::ENGINEER_4 + self::EMPLOYEE_4,

        MANAGER_1_LOADED = self::MANAGER_1 + self::EMPLOYEE_1,
        MANAGER_3_LOADED = self::MANAGER_3 + self::EMPLOYEE_3,

        EMPLOYEE_ALL_LOADED = [self::EMPLOYEE_1, self::EMPLOYEE_2, self::EMPLOYEE_3, self::EMPLOYEE_4],
        ENGINEER_ALL_LOADED = [self::ENGINEER_2_LOADED, self::ENGINEER_4_LOADED],
        MANAGER_ALL_LOADED = [self::MANAGER_1_LOADED, self::MANAGER_3_LOADED];

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('employee', [
            'id'        => 'integer',
            'name'      => 'string',
            'age'       => 'integer,nullable',
        ], pk: ['id']);
        $this->makeTable('engineer', [
            'id'        => 'integer',
            'level'     => 'integer',
        ], fk: [
            'id' => ['table' => 'employee', 'column' => 'id']
        ], pk: ['id']);
        $this->makeTable('manager', [
            'id'        => 'integer',
            'rank'      => 'string',
        ], fk: [
            'id' => ['table' => 'employee', 'column' => 'id']
        ], pk: ['id']);

        $this->getDatabase()->table('employee')->insertMultiple(
            ['id', 'name', 'age'],
            [
                self::EMPLOYEE_1,
                self::EMPLOYEE_2,
                self::EMPLOYEE_3,
                self::EMPLOYEE_4,
            ]
        );

        $this->getDatabase()->table('engineer')->insertMultiple(
            ['id', 'level'],
            [
                self::ENGINEER_2,
                self::ENGINEER_4,
            ]
        );

        $this->getDatabase()->table('manager')->insertMultiple(
            ['id', 'rank'],
            [
                self::MANAGER_1,
                self::MANAGER_3,
            ]
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));

    }

    public function testSelectEmployeeAllData(): void
    {
        $selector = new Select($this->orm, Employee::class);

        $this->assertEquals(self::EMPLOYEE_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEmployeeDataFirst(): void
    {
        $selector = (new Select($this->orm, Employee::class))->limit(1);

        $this->assertEquals(self::EMPLOYEE_1, $selector->fetchData()[0]);
    }

    public function testSelectEngineerAllData(): void
    {
        $selector = (new Select($this->orm, Engineer::class));

        $this->assertEquals(self::ENGINEER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEngineerDataFirst(): void
    {
        $selector = (new Select($this->orm, Engineer::class))->limit(1);

        $this->assertEquals(self::ENGINEER_2_LOADED, $selector->fetchData()[0]);
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

    #[ArrayShape([Employee::class => "array", Engineer::class => "array", Manager::class => "array"])]
    private function getSchemaArray(): array
    {
        return [
            Employee::class => [
                Schema::ROLE        => 'employee',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'employee',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name', 'age'],
                Schema::TYPECAST    => ['id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
            Engineer::class => [
                Schema::ROLE        => 'engineer',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'engineer',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'level'],
                Schema::TYPECAST    => ['id' => 'int', 'level' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
            Manager::class => [
                Schema::ROLE        => 'manager',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'manager',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'rank'],
                Schema::TYPECAST    => ['id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
        ];
    }
}
