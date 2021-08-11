<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Inheritance\JTI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Inheritance\Fixture\Engineer;
use Cycle\ORM\Tests\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Inheritance\Fixture\Programator;

abstract class SingleTableTest extends SimpleCasesTest
{
    protected const
        EMPLOYEE_1 = ['employee_id' => 1, 'name' => 'John', 'age' => 38],
        EMPLOYEE_2 = ['employee_id' => 2, 'name' => 'Anton', 'age' => 35],
        EMPLOYEE_3 = ['employee_id' => 3, 'name' => 'Kentarius', 'age' => 27],
        EMPLOYEE_4 = ['employee_id' => 4, 'name' => 'Valeriy', 'age' => 32],

        ENGINEER_2 = ['discriminator' => 'engineer', 'role_id' => 2, 'level' => 8, 'rank' => ''],
        ENGINEER_4 = ['discriminator' => 'engineer', 'role_id' => 4, 'level' => 10, 'rank' => ''],
        MANAGER_1  = ['discriminator' => 'manager', 'role_id' => 1, 'level' => 0, 'rank' => 'top'],
        MANAGER_3  = ['discriminator' => 'manager', 'role_id' => 3, 'level' => 0, 'rank' => 'bottom'],

        PROGRAMATOR_2 = ['subrole_id' => 2, 'language' => 'php'],
        PROGRAMATOR_4 = ['subrole_id' => 4, 'language' => 'go'],

        EMPLOYEE_1_LOADED = self::EMPLOYEE_1,
        EMPLOYEE_2_LOADED = self::EMPLOYEE_2,
        EMPLOYEE_3_LOADED = self::EMPLOYEE_3,
        EMPLOYEE_4_LOADED = self::EMPLOYEE_4,

        ENGINEER_2_LOADED = self::ENGINEER_2 + self::EMPLOYEE_2_LOADED,
        ENGINEER_4_LOADED = self::ENGINEER_4 + self::EMPLOYEE_4_LOADED,

        PROGRAMATOR_2_LOADED = self::PROGRAMATOR_2 + self::ENGINEER_2_LOADED,
        PROGRAMATOR_4_LOADED = self::PROGRAMATOR_4 + self::ENGINEER_4_LOADED,

        MANAGER_1_LOADED = self::MANAGER_1 + self::EMPLOYEE_1_LOADED,
        MANAGER_3_LOADED = self::MANAGER_3 + self::EMPLOYEE_3_LOADED,

        EMPLOYEE_ALL_LOADED = [
            self::EMPLOYEE_1_LOADED,
            self::EMPLOYEE_2_LOADED,
            self::EMPLOYEE_3_LOADED,
            self::EMPLOYEE_4_LOADED,
        ],
        EMPLOYEE_INHERITED_LOADED = [
            self::MANAGER_1_LOADED,
            self::PROGRAMATOR_2_LOADED,
            self::MANAGER_3_LOADED,
            self::PROGRAMATOR_4_LOADED,
        ],
        ENGINEER_ALL_LOADED = [self::ENGINEER_2_LOADED, self::ENGINEER_4_LOADED],
        ROLES_ALL_LOADED = [self::EMPLOYEE_1_LOADED, self::ENGINEER_2_LOADED, self::EMPLOYEE_3_LOADED, self::ENGINEER_4_LOADED],
        PROGRAMATOR_ALL_LOADED = [self::PROGRAMATOR_2_LOADED, self::PROGRAMATOR_4_LOADED],
        MANAGER_ALL_LOADED = [self::MANAGER_1_LOADED, self::MANAGER_3_LOADED],

        // todo: remove when STI will support classless entities and Schema::CHILDREN's roles
        PROGRAMATOR_ROLE = Programator::class,
        ENGINEER_ROLE = Engineer::class;

    public function setUp(): void
    {
        JtiBaseTest::setUp();
        $this->logger->hide();

        $this->makeTable('employee_table', [
            'employee_id_column' => 'integer',
            'name_column' => 'string',
            'age' => 'integer,nullable',
        ], pk: ['employee_id_column']);
        $this->makeTable('role_table', [
            'role_id_column' => 'integer,nullable',
            'subrole_id_column' => 'integer,nullable',
            'discriminator' => 'string,nullable',
            'level' => 'integer,nullable',
            'rank' => 'string,nullable',
            'language' => 'string,nullable',
        ], fk: [
            'role_id_column' => ['table' => 'employee_table', 'column' => 'employee_id_column'],
            'subrole_id_column' => ['table' => 'role_table', 'column' => 'role_id_column'],
        ]);

        $this->getDatabase()->table('employee_table')->insertMultiple(
            ['employee_id_column', 'name_column', 'age'],
            [
                self::EMPLOYEE_1,
                self::EMPLOYEE_2,
                self::EMPLOYEE_3,
                self::EMPLOYEE_4,
            ]
        );
        $this->getDatabase()->table('role_table')->insertMultiple(
            ['discriminator', 'role_id_column', 'level', 'rank'],
            [
                self::MANAGER_1,
                self::ENGINEER_2,
                self::MANAGER_3,
                self::ENGINEER_4,
            ]
        );
        $this->getDatabase()->table('role_table')->insertMultiple(
            ['subrole_id_column', 'language'],
            [
                self::PROGRAMATOR_2,
                self::PROGRAMATOR_4,
            ]
        );
        $this->logger->display();
    }

    protected function getSchemaArray(): array
    {
        return [
            Employee::class => [
                SchemaInterface::ROLE        => 'employee',
                SchemaInterface::MAPPER      => Mapper::class,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'employee_table',
                SchemaInterface::PRIMARY_KEY => 'employee_id',
                SchemaInterface::COLUMNS     => [
                    'employee_id' => 'employee_id_column',
                    'name' => 'name_column',
                    'age',
                ],
                SchemaInterface::TYPECAST    => ['employee_id' => 'int', 'age' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
            Manager::class => [
                SchemaInterface::ROLE        => 'role',
            ],
            Engineer::class => [
                SchemaInterface::ROLE        => 'role',
            ],
            'role' => [
                SchemaInterface::MAPPER      => Mapper::class,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'role_table',
                SchemaInterface::PARENT      => 'employee',
                SchemaInterface::CHILDREN    => [
                    'engineer' => Engineer::class,
                    'manager'  => Manager::class,
                ],
                SchemaInterface::PRIMARY_KEY => 'role_id',
                SchemaInterface::DISCRIMINATOR => 'discriminator',
                SchemaInterface::COLUMNS     => ['role_id' => 'role_id_column', 'level', 'rank', 'discriminator'],
                SchemaInterface::TYPECAST    => ['role_id' => 'int', 'level' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
            Programator::class => [
                SchemaInterface::ROLE        => 'subrole',
                SchemaInterface::MAPPER      => Mapper::class,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'role_table',
                SchemaInterface::PARENT      => Engineer::class,
                SchemaInterface::PRIMARY_KEY => 'subrole_id',
                SchemaInterface::COLUMNS     => ['subrole_id' => 'subrole_id_column', 'language'],
                SchemaInterface::TYPECAST    => ['subrole_id' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
        ];
    }

    public function testFetchAllChildren(): void
    {
        /** @var Manager[]|Engineer[] $entities */
        $entities = (new Select($this->orm, 'role'))->fetchAll();

        $this->assertSame(1, $entities[0]->role_id);
        $this->assertInstanceOf(Manager::class, $entities[0]);
        $this->assertSame(2, $entities[1]->role_id);
        $this->assertInstanceOf(Engineer::class, $entities[1]);
        $this->assertSame(3, $entities[2]->role_id);
        $this->assertInstanceOf(Manager::class, $entities[2]);
        $this->assertSame(4, $entities[3]->role_id);
        $this->assertInstanceOf(Engineer::class, $entities[3]);
    }

    public function testSelectEngineerAllDataWithInheritance(): void
    {
        $selector = (new Select($this->orm, static::ENGINEER_ROLE))
            // todo: this condition should be added automatically by STI
            ->where('discriminator', '=', 'engineer');

        $this->assertEquals(static::PROGRAMATOR_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEngineerAllDataWithoutInheritance(): void
    {
        $selector = (new Select($this->orm, static::ENGINEER_ROLE))
            ->loadSubclasses(false)
            // todo: this condition should be added automatically by STI
            ->where('discriminator', '=', 'engineer');

        $this->assertEquals(static::ENGINEER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEngineerDataFirstWithInheritance(): void
    {
        $selector = (new Select($this->orm, static::ENGINEER_ROLE))
            // todo: this condition should be added automatically by STI
            ->where('discriminator', '=', 'engineer')
            ->limit(1);

        $this->assertEquals(static::PROGRAMATOR_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectEngineerDataFirstWithoutInheritance(): void
    {
        $selector = (new Select($this->orm, static::ENGINEER_ROLE))
            ->loadSubclasses(false)
            // todo: this condition should be added automatically by STI
            ->where('discriminator', '=', 'engineer')
            ->limit(1);

        $this->assertEquals(static::ENGINEER_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectEngineerEntityFirstWithInheritance(): void
    {
        $selector = (new Select($this->orm, static::ENGINEER_ROLE))
            // todo: this condition should be added automatically by STI
            ->where('discriminator', '=', 'engineer')
            ->limit(1);

        $this->assertInstanceof(Programator::class, $selector->fetchOne());
    }

    public function testSelectManagerAllData(): void
    {
        $selector = (new Select($this->orm, Manager::class))
            // todo: this condition should be added automatically by STI
            ->where('discriminator', '=', 'manager');

        $this->assertEquals(static::MANAGER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectManagerDataFirst(): void
    {
        $selector = (new Select($this->orm, Manager::class))
            // todo: this condition should be added automatically by STI
            ->where('discriminator', '=', 'manager')
            ->limit(1);

        $this->assertEquals(static::MANAGER_1_LOADED, $selector->fetchData()[0]);
    }

    public function testCreateProgramator(): void
    {
        $programator = new Programator();
        $programator->name = 'Merlin';
        $programator->level = 50;
        $programator->language = 'VanillaJS';

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);

        $this->assertSame(5, $programator->employee_id);
        $this->assertSame(5, $programator->role_id);
        $this->assertSame(5, $programator->subrole_id);

        /** @var Programator $programator */
        $programator = (new Select($this->orm->withHeap(new Heap()), Programator::class))
            ->wherePK($programator->subrole_id)
            ->fetchOne();
        $this->assertSame(5, $programator->employee_id);
        $this->assertSame(5, $programator->role_id);
        $this->assertSame(5, $programator->subrole_id);
        $this->assertSame('Merlin', $programator->name);
        $this->assertSame(50, $programator->level);
        $this->assertSame('VanillaJS', $programator->language);
    }
}
