<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Engineer;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Programator;

abstract class CompositePKTest extends SimpleCasesTest
{
    protected const EMPLOYEE_DEFAULT_SORTING = ['id' => 'ASC', 'employee_id' => 'ASC'];
    protected const
        EMPLOYEE_1 = ['id' => 1, 'employee_id' => 2, 'name' => 'John', 'age' => 38];
    protected const
        EMPLOYEE_2 = ['id' => 2, 'employee_id' => 3, 'name' => 'Anton', 'age' => 35];
    protected const
        EMPLOYEE_3 = ['id' => 3, 'employee_id' => 4, 'name' => 'Kentarius', 'age' => 27];
    protected const
        EMPLOYEE_4 = ['id' => 4, 'employee_id' => 5, 'name' => 'Valeriy', 'age' => 32];
    protected const
        ENGINEER_2 = ['_type' => 'engineer', 'id' => 2, 'role_id' => 3, 'level' => 8, 'rank' => null];
    protected const
        ENGINEER_4 = ['_type' => 'engineer', 'id' => 4, 'role_id' => 5, 'level' => 10, 'rank' => null];
    protected const
        MANAGER_1 = ['_type' => 'manager', 'id' => 1, 'role_id' => 2, 'level' => null, 'rank' => 'top'];
    protected const
        MANAGER_3 = ['_type' => 'manager', 'id' => 3, 'role_id' => 4, 'level' => null, 'rank' => 'bottom'];
    protected const
        ENGINEER_2_PK = ['id' => 2, 'role_id' => 3];
    protected const
        EMPLOYEE_2_PK = ['id' => 2, 'employee_id' => 3];
    protected const
        PROGRAMATOR_2_PK = ['second_id' => 3, 'subrole_id' => 2];
    protected const
        PROGRAMATOR_2 = ['second_id' => 3, 'subrole_id' => 2, 'language' => 'php'];
    protected const
        PROGRAMATOR_4 = ['second_id' => 5, 'subrole_id' => 4, 'language' => 'go'];
    protected const
        EMPLOYEE_1_LOADED = self::EMPLOYEE_1;
    protected const
        EMPLOYEE_2_LOADED = self::EMPLOYEE_2;
    protected const
        EMPLOYEE_3_LOADED = self::EMPLOYEE_3;
    protected const
        EMPLOYEE_4_LOADED = self::EMPLOYEE_4;
    protected const
        ENGINEER_2_LOADED = self::ENGINEER_2 + self::EMPLOYEE_2_LOADED;
    protected const
        ENGINEER_4_LOADED = self::ENGINEER_4 + self::EMPLOYEE_4_LOADED;
    protected const
        PROGRAMATOR_2_LOADED = self::PROGRAMATOR_2 + self::ENGINEER_2_LOADED;
    protected const
        PROGRAMATOR_4_LOADED = self::PROGRAMATOR_4 + self::ENGINEER_4_LOADED;
    protected const
        MANAGER_1_LOADED = self::MANAGER_1 + self::EMPLOYEE_1_LOADED;
    protected const
        MANAGER_3_LOADED = self::MANAGER_3 + self::EMPLOYEE_3_LOADED;
    protected const
        EMPLOYEE_ALL_LOADED = [
            self::EMPLOYEE_1_LOADED,
            self::EMPLOYEE_2_LOADED,
            self::EMPLOYEE_3_LOADED,
            self::EMPLOYEE_4_LOADED,
        ];
    protected const
        EMPLOYEE_INHERITED_LOADED = [
            self::MANAGER_1_LOADED,
            self::PROGRAMATOR_2_LOADED,
            self::MANAGER_3_LOADED,
            self::PROGRAMATOR_4_LOADED,
        ];
    protected const
        ENGINEER_ALL_LOADED = [self::ENGINEER_2_LOADED, self::ENGINEER_4_LOADED];
    protected const
        ENGINEER_INHERITED_LOADED = [self::PROGRAMATOR_2_LOADED, self::PROGRAMATOR_4_LOADED];
    protected const
        PROGRAMATOR_ALL_LOADED = [self::PROGRAMATOR_2_LOADED, self::PROGRAMATOR_4_LOADED];
    protected const
        MANAGER_ALL_LOADED = [self::MANAGER_1_LOADED, self::MANAGER_3_LOADED];
    protected const
        // todo: remove when STI will support classless entities and Schema::CHILDREN's roles
        PROGRAMATOR_ROLE = Programator::class;
    protected const
        ENGINEER_ROLE = Engineer::class;

    public function setUp(): void
    {
        JtiBaseTest::setUp();

        $this->makeTable('employee', [
            'id' => 'integer',
            'employee_id' => 'integer',
            'name_column' => 'string',
            'age' => 'integer,nullable',
        ], pk: ['id', 'employee_id']);
        $this->makeIndex('employee', ['employee_id'], false);

        $this->makeTable('role', [
            'id' => 'integer',
            'role_id' => 'integer',
            '_type' => 'string',
            'level' => 'integer,nullable',
            'rank' => 'string,nullable',
        ], fk: [
            ['from' => ['id', 'role_id'], 'table' => 'employee', 'column' => ['id', 'employee_id']],
        ], pk: ['id', 'role_id']);
        $this->makeIndex('role', ['role_id', 'id'], true);

        $this->makeTable('programator', [
            'id' => 'integer',
            'subrole_id' => 'integer',
            'language' => 'string',
        ], fk: [
            ['from' => ['id', 'subrole_id'], 'table' => 'role', 'column' => ['role_id', 'id']],
        ], pk: ['id', 'subrole_id']);

        $this->getDatabase()->table('employee')->insertMultiple(
            ['id', 'employee_id', 'name_column', 'age'],
            [
                self::EMPLOYEE_1,
                self::EMPLOYEE_2,
                self::EMPLOYEE_3,
                self::EMPLOYEE_4,
            ]
        );
        $this->getDatabase()->table('role')->insertMultiple(
            ['_type', 'id', 'role_id', 'level', 'rank'],
            [
                self::MANAGER_1,
                self::ENGINEER_2,
                self::MANAGER_3,
                self::ENGINEER_4,
            ]
        );
        $this->getDatabase()->table('programator')->insertMultiple(
            ['id', 'subrole_id', 'language'],
            [
                self::PROGRAMATOR_2,
                self::PROGRAMATOR_4,
            ]
        );
    }

    public function testSelectEngineerAllDataWithInheritance(): void
    {
        $selector = (new Select($this->orm, Engineer::class))
            // todo: this condition should be added automatically by STI
            ->where('_type', '=', 'engineer');

        $this->assertEquals(static::ENGINEER_INHERITED_LOADED, $selector->fetchData());
    }

    public function testSelectEngineerAllDataWithoutInheritance(): void
    {
        $selector = (new Select($this->orm, Engineer::class))
            // todo: this condition should be added automatically by STI
            ->loadSubclasses(false)
            ->where('_type', '=', 'engineer');

        $this->assertEquals(static::ENGINEER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEngineerDataFirstWithInheritance(): void
    {
        $selector = (new Select($this->orm, Engineer::class))
            // todo: this condition should be added automatically by STI
            ->where('_type', '=', 'engineer')
            ->limit(1);

        $this->assertEquals(static::PROGRAMATOR_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectEngineerDataFirstWithoutInheritance(): void
    {
        $selector = (new Select($this->orm, Engineer::class))
            // todo: this condition should be added automatically by STI
            ->where('_type', '=', 'engineer')
            ->loadSubclasses(false)
            ->limit(1);

        $this->assertEquals(static::ENGINEER_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectEngineerEntityFirstWithInheritance(): void
    {
        $selector = (new Select($this->orm, static::ENGINEER_ROLE))
            // todo: this condition should be added automatically by STI
            ->where('_type', '=', 'engineer')
            ->limit(1);

        $this->assertInstanceof(Programator::class, $selector->fetchOne());
    }

    public function testSelectManagerAllData(): void
    {
        $selector = (new Select($this->orm, Manager::class))
            // todo: this condition should be added automatically by STI
            ->where('_type', '=', 'manager');

        $this->assertEquals(static::MANAGER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectManagerDataFirst(): void
    {
        $selector = (new Select($this->orm, Manager::class))
            // todo: this condition should be added automatically by STI
            ->where('_type', '=', 'manager')
            ->limit(1);

        $this->assertEquals(static::MANAGER_1_LOADED, $selector->fetchData()[0]);
    }

    public function testCreateProgramator(): void
    {
        $programator = new Programator();
        $programator->id = 10;
        $programator->employee_id = 11;
        $programator->name = 'Merlin';
        $programator->level = 50;
        $programator->language = 'VanillaJS';

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);

        /** @var Programator $programator */
        $programator = (new Select($this->orm->with(heap: new Heap()), Programator::class))
            ->wherePK(['second_id' => 11, 'subrole_id' => 10])
            ->fetchOne();
        $this->assertSame(10, $programator->id);
        $this->assertSame(11, $programator->employee_id);
        $this->assertSame(11, $programator->role_id);
        $this->assertSame(10, $programator->subrole_id);
        $this->assertSame(11, $programator->second_id);
        $this->assertSame('Merlin', $programator->name);
        $this->assertSame(50, $programator->level);
        $this->assertSame('VanillaJS', $programator->language);
    }

    protected function getSchemaArray(): array
    {
        return [
            Employee::class => [
                SchemaInterface::ROLE => 'employee',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'employee',
                SchemaInterface::PRIMARY_KEY => ['id', 'employee_id'],
                SchemaInterface::COLUMNS => [
                    'id',
                    'employee_id',
                    'name' => 'name_column',
                    'age',
                ],
                SchemaInterface::TYPECAST => ['id' => 'int', 'employee_id' => 'int', 'age' => 'int'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
            Manager::class => [
                SchemaInterface::ROLE => 'role',
            ],
            Engineer::class => [
                SchemaInterface::ROLE => 'role',
            ],
            'role' => [
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'role',
                SchemaInterface::PARENT => Employee::class,
                SchemaInterface::CHILDREN => [
                    'engineer' => Engineer::class,
                    'manager' => Manager::class,
                ],
                SchemaInterface::PRIMARY_KEY => ['id', 'role_id'],
                SchemaInterface::COLUMNS => ['id', 'role_id', 'level', 'rank', '_type'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'role_id' => 'int', 'level' => 'int'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
            Programator::class => [
                SchemaInterface::ROLE => 'programator',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'programator',
                SchemaInterface::PARENT => Engineer::class,
                SchemaInterface::PARENT_KEY => ['role_id', 'id'],
                SchemaInterface::PRIMARY_KEY => ['second_id', 'subrole_id'],
                SchemaInterface::COLUMNS => ['second_id' => 'id', 'subrole_id', 'language'],
                SchemaInterface::TYPECAST => ['second_id' => 'int', 'subrole_id' => 'int'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ];
    }
}
