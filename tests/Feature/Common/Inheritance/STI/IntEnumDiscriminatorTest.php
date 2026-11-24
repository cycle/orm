<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\STI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\EmployeeKind;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Manager;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class IntEnumDiscriminatorTest extends StiBaseTest
{
    protected const BASE_ROLE = 'employee';
    protected const MANAGER_ROLE = 'manager';

    #[Test]
    public function testChildClassResolvedFromIntScalarInDatabase(): void
    {
        [$first, $second] = (new Select($this->orm, Employee::class))->orderBy('id')->fetchAll();

        Assert::instanceOf($first, Manager::class);
        Assert::instanceOf($second, Employee::class);
        Assert::false($second instanceof Manager);
    }

    #[Test]
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

        Assert::same((int) $row['kind'], EmployeeKind::Manager->value);
    }

    #[Test]
    public function testMakeResolvesChildClassFromIntEnumInputData(): void
    {
        $entity = $this->orm->make(self::BASE_ROLE, [
            '_type' => EmployeeKind::Manager,
            'name' => 'Senya',
            'email' => 'sene4ka@hamster.me',
            'age' => 12,
        ]);

        Assert::instanceOf($entity, Manager::class);
    }

    #[Test]
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

        Assert::instanceOf($loaded, Manager::class);
    }

    #[BeforeTest]
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

    protected function getSchemaArray(): array
    {
        return [
            self::BASE_ROLE => [
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
