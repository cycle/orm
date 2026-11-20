<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\STI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Manager;
use Cycle\Testing\Database\Assert\QueryAssert;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class SimpleTest extends StiBaseTest
{
    protected const BASE_ROLE = 'employee';
    protected const MANAGER_ROLE = 'manager';
    protected const EMPLOYEE_VALUE = 'employee';
    protected const MANAGER_VALUE = 'manager';
    protected const EMPLOYEE_1 = [
        '_type' => self::MANAGER_VALUE,
        'name' => 'John',
        'email' => 'captain@black.sea',
        'age' => 38,
    ];
    protected const EMPLOYEE_2 = [
        '_type' => self::EMPLOYEE_VALUE,
        'name' => 'Anton',
        'email' => 'antonio@mail.org',
        'age' => 35,
    ];
    protected const EMPLOYEE_3 = [
        '_type' => self::MANAGER_VALUE,
        'name' => 'Kentarius',
        'email' => 'grove@save.com',
        'age' => 27,
    ];
    protected const EMPLOYEE_4 = [
        '_type' => null,
        'name' => 'Max',
        'email' => 'valhall@go.to',
        'age' => 32,
    ];
    protected const EMPLOYEE_1_LOADED = ['id' => 1] + self::EMPLOYEE_1;
    protected const EMPLOYEE_2_LOADED = ['id' => 2] + self::EMPLOYEE_2;
    protected const EMPLOYEE_3_LOADED = ['id' => 3] + self::EMPLOYEE_3;
    protected const EMPLOYEE_4_LOADED = ['id' => 4] + self::EMPLOYEE_4;
    protected const EMPLOYEE_LOADED_ALL = [
        self::EMPLOYEE_1_LOADED,
        self::EMPLOYEE_2_LOADED,
        self::EMPLOYEE_3_LOADED,
        self::EMPLOYEE_4_LOADED,
    ];

    // Filtered on discriminator value
    protected const EMPLOYEES_LOADED_ALL = [self::EMPLOYEE_2_LOADED, self::EMPLOYEE_4_LOADED];
    protected const MANAGERS_LOADED_ALL = [self::EMPLOYEE_1_LOADED, self::EMPLOYEE_3_LOADED];

    /**
     * Discriminator column name
     */
    protected static string $discriminator = 'discriminator_value';

    // Basics

    #[Test]
    public function testFetchBaseData(): void
    {
        $selector = new Select($this->orm, Employee::class);

        Assert::equals($selector->fetchData(), self::EMPLOYEE_LOADED_ALL);
    }

    #[Test]
    public function testEntityClass(): void
    {
        $selector = new Select($this->orm, Employee::class);
        [$a, $b, $c, $d] = $selector->orderBy('id')->fetchAll();

        Assert::instanceOf($a, Manager::class);

        Assert::instanceOf($b, Employee::class);
        Assert::false($b instanceof Manager);

        Assert::instanceOf($c, Manager::class);

        Assert::instanceOf($d, Employee::class);
        Assert::false($d instanceof Manager);
    }

    #[Test]
    public function testAutomaticallyStoreDiscriminatorValue(): void
    {
        $employee = new Employee();
        $employee->email = 'user@email.com';
        $employee->name = 'User';
        $employee->age = 42;

        $manager = new Manager();
        $manager->email = 'admin@email.com';
        $manager->name = 'Manager';
        $manager->age = 69;

        $this->save($employee, $manager);

        $fetchedEmployee = (new Select($this->orm->withHeap(new Heap()), Employee::class))
            ->wherePK($employee->id)->fetchOne();
        Assert::instanceOf($fetchedEmployee, Employee::class);
        Assert::false($fetchedEmployee instanceof Manager);

        $fetchedManager = (new Select($this->orm->withHeap(new Heap()), Employee::class))
            ->wherePK($manager->id)->fetchOne();
        Assert::instanceOf($fetchedManager, Manager::class);
    }

    // Using of child role

    #[Test]
    public function testSelectUsingBaseRole(): void
    {
        $selector = new Select($this->orm, self::BASE_ROLE);

        Assert::equals($selector->fetchData(), self::EMPLOYEE_LOADED_ALL);
    }

    #[Test]
    public function testSelectUsingChildClass(): void
    {
        $selector = new Select($this->orm, Manager::class);

        Assert::equals($selector->fetchData(), self::EMPLOYEE_LOADED_ALL);
    }

    #[Test]
    public function testSelectUsingChildRole(): void
    {
        $selector = new Select($this->orm, self::MANAGER_ROLE);

        Assert::equals($selector->fetchData(), self::EMPLOYEE_LOADED_ALL);
    }

    #[Test]
    public function testMakeUsingRole(): void
    {
        $employee = new Employee();
        $employee->email = 'user@email.com';
        $employee->name = 'User';
        $employee->age = 42;

        $manager = new Manager();
        $manager->email = 'admin@email.com';
        $manager->name = 'Manager';
        $manager->age = 69;

        $this->save($employee, $manager);

        $fetchedEmployee = (new Select($this->orm->withHeap(new Heap()), Employee::class))
            ->wherePK($employee->id)->fetchOne();
        Assert::instanceOf($fetchedEmployee, Employee::class);
        Assert::false($fetchedEmployee instanceof Manager);

        $fetchedManager = (new Select($this->orm->withHeap(new Heap()), Employee::class))
            ->wherePK($manager->id)->fetchOne();
        Assert::instanceOf($fetchedManager, Manager::class);
    }

    #[Test]
    public function testMultipleDiscriminaorValueOnOneRole(): void
    {
        $schemaArray = $this->getSchemaArray();
        $schemaArray[self::BASE_ROLE][SchemaInterface::CHILDREN][self::MANAGER_VALUE . '_two'] = Manager::class;
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $entity = $this->orm->make(self::BASE_ROLE, [
            '_type' => self::MANAGER_VALUE . '_two',
            'name' => 'Senya',
            'email' => 'sene4ka@hamster.me',
            'age' => 12,
        ]);

        Assert::instanceOf($entity, Manager::class);

        QueryAssert::writes(1, fn() => $this->save($entity));

        QueryAssert::writes(0, fn() => $this->save($entity));

        $loadedEntity = (new Select($this->orm->withHeap(new Heap()), Employee::class))
            ->wherePK($entity->id)->fetchData();
        Assert::same($loadedEntity[0]['_type'], self::MANAGER_VALUE . '_two');
    }

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('employee_table', [
            self::$discriminator => 'string,nullable',
            'id' => 'primary',
            'name' => 'string',
            'email' => 'string',
            'age' => 'int',
        ]);

        $this->getDatabase()->table('employee_table')->insertMultiple(
            [self::$discriminator, 'name', 'email', 'age'],
            [self::EMPLOYEE_1, self::EMPLOYEE_2, self::EMPLOYEE_3, self::EMPLOYEE_4],
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    protected function getSchemaArray(): array
    {
        return [
            self::BASE_ROLE => [
                SchemaInterface::ENTITY => Employee::class,
                SchemaInterface::CHILDREN => [
                    self::MANAGER_VALUE => Manager::class,
                ],
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'employee_table',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', '_type' => self::$discriminator, 'name', 'email', 'age'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'age' => 'int'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
            self::MANAGER_ROLE => [
                SchemaInterface::ENTITY => Manager::class,
                // SchemaInterface::ROLE   => Employee::class,
            ],
        ];
    }
}
