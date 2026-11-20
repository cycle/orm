<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\STI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\EmployeeType;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Manager;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class EnumDiscriminatorTest extends StiBaseTest
{
    protected const BASE_ROLE = 'employee';
    protected const MANAGER_ROLE = 'manager';

    protected static string $discriminator = 'discriminator_value';

    #[Test]
    public function testChildClassResolvedFromScalarInDatabase(): void
    {
        $selector = new Select($this->orm, Employee::class);
        [$first, $second] = $selector->orderBy('id')->fetchAll();

        Assert::instanceOf($first, Manager::class);
        Assert::instanceOf($second, Employee::class);
        Assert::false($second instanceof Manager);
    }

    #[Test]
    public function testFetchedDataExposesEnumCaseAtDiscriminatorColumn(): void
    {
        $rows = (new Select($this->orm, Employee::class))->orderBy('id')->fetchData();

        Assert::same($rows[0]['_type'], EmployeeType::Manager);
        Assert::same($rows[1]['_type'], EmployeeType::Employee);
    }

    #[Test]
    public function testPersistedChildWritesScalarToDatabase(): void
    {
        $manager = new Manager();
        $manager->name = 'Manager';
        $manager->email = 'admin@email.com';
        $manager->age = 69;

        $this->save($manager);

        $row = $this->getDatabase()
            ->table('employee_table')
            ->select()
            ->where('id', $manager->id)
            ->fetchAll()[0];

        Assert::same($row[self::$discriminator], 'manager');
    }

    #[Test]
    public function testRoundTripChildEntity(): void
    {
        $manager = new Manager();
        $manager->name = 'Manager';
        $manager->email = 'admin@email.com';
        $manager->age = 69;

        $this->save($manager);

        $loaded = (new Select($this->orm->withHeap(new Heap()), Employee::class))
            ->wherePK($manager->id)
            ->fetchOne();

        Assert::instanceOf($loaded, Manager::class);
    }

    #[Test]
    public function testMakeResolvesChildClassFromEnumInputData(): void
    {
        // Defensive: explicit BackedEnum passed as discriminator value should still resolve.
        $entity = $this->orm->make(self::BASE_ROLE, [
            '_type' => EmployeeType::Manager,
            'name' => 'Senya',
            'email' => 'sene4ka@hamster.me',
            'age' => 12,
        ]);

        Assert::instanceOf($entity, Manager::class);
    }

    #[Test]
    public function testNoExtraWritesAfterLoadAndResave(): void
    {
        /** @var Manager $manager */
        $manager = (new Select($this->orm, Employee::class))->orderBy('id')->fetchOne();
        Assert::instanceOf($manager, Manager::class);

        $this->captureWriteQueries();
        $this->save($manager);
        $this->assertNumWrites(0);
    }

    #[Test]
    public function testUpdateChildEntityKeepsScalarDiscriminatorInDatabase(): void
    {
        /** @var Manager $manager */
        $manager = (new Select($this->orm, Employee::class))->orderBy('id')->fetchOne();
        Assert::instanceOf($manager, Manager::class);

        $manager->name = 'Renamed';
        $this->save($manager);

        $row = $this->getDatabase()
            ->table('employee_table')
            ->select()
            ->where('id', $manager->id)
            ->fetchAll()[0];

        Assert::same($row[self::$discriminator], 'manager');
        Assert::same($row['name'], 'Renamed');
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
            [
                ['_type' => 'manager', 'name' => 'John', 'email' => 'captain@black.sea', 'age' => 38],
                ['_type' => 'employee', 'name' => 'Anton', 'email' => 'antonio@mail.org', 'age' => 35],
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
                    'manager' => Manager::class,
                ],
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'employee_table',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', '_type' => self::$discriminator, 'name', 'email', 'age'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'age' => 'int', '_type' => EmployeeType::class],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
            self::MANAGER_ROLE => [
                SchemaInterface::ENTITY => Manager::class,
            ],
        ];
    }
}
