<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\EmployeeKind;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Manager;

abstract class IntEnumDiscriminatorTest extends StiBaseTest
{
    protected const BASE_ROLE = 'employee';
    protected const MANAGER_ROLE = 'manager';

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('employee_table', [
            'kind' => 'int,nullable',
            'id' => 'primary',
            'name' => 'string',
            'email' => 'string',
            'age' => 'int',
        ]);

        $this->getDatabase()->table('employee_table')->insertMultiple(
            ['kind', 'name', 'email', 'age'],
            [
                ['kind' => EmployeeKind::Manager->value, 'name' => 'John', 'email' => 'j@x', 'age' => 38],
                ['kind' => EmployeeKind::Employee->value, 'name' => 'Anton', 'email' => 'a@x', 'age' => 35],
            ],
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    public function testChildClassResolvedFromIntScalarInDatabase(): void
    {
        [$first, $second] = (new Select($this->orm, Employee::class))->orderBy('id')->fetchAll();

        $this->assertInstanceOf(Manager::class, $first);
        $this->assertInstanceOf(Employee::class, $second);
        $this->assertNotInstanceOf(Manager::class, $second);
    }

    public function testPersistedChildWritesIntScalar(): void
    {
        $manager = new Manager();
        $manager->name = 'New Manager';
        $manager->email = 'new@manager';
        $manager->age = 40;
        $this->save($manager);

        $row = $this->getDatabase()
            ->table('employee_table')
            ->select()
            ->where('id', $manager->id)
            ->fetchAll()[0];

        $this->assertSame(EmployeeKind::Manager->value, (int) $row['kind']);
    }

    public function testMakeResolvesChildClassFromIntEnumInputData(): void
    {
        $entity = $this->orm->make(static::BASE_ROLE, [
            '_type' => EmployeeKind::Manager,
            'name' => 'Senya',
            'email' => 'sene4ka@hamster.me',
            'age' => 12,
        ]);

        $this->assertInstanceOf(Manager::class, $entity);
    }

    public function testRoundTripChildEntity(): void
    {
        $manager = new Manager();
        $manager->name = 'Round-trip';
        $manager->email = 'rt@manager';
        $manager->age = 42;
        $this->save($manager);

        $loaded = (new Select($this->orm->withHeap(new Heap()), Employee::class))
            ->wherePK($manager->id)
            ->fetchOne();

        $this->assertInstanceOf(Manager::class, $loaded);
    }

    protected function getSchemaArray(): array
    {
        return [
            static::BASE_ROLE => [
                SchemaInterface::ENTITY => Employee::class,
                SchemaInterface::CHILDREN => [
                    EmployeeKind::Manager->value => Manager::class,
                ],
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'employee_table',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', '_type' => 'kind', 'name', 'email', 'age'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'age' => 'int', '_type' => EmployeeKind::class],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
            self::MANAGER_ROLE => [
                SchemaInterface::ENTITY => Manager::class,
            ],
        ];
    }
}
