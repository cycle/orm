<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Employee;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Engineer;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Manager;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Programator;
use Cycle\ORM\Transaction;

abstract class SimpleCasesTest extends JtiBaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('employee', [
            'id'          => 'integer',
            'name_column' => 'string',
            'age'         => 'integer,nullable',
        ], pk: ['id']);
        $this->makeTable('engineer', [
            'id'        => 'integer',
            'level'     => 'integer',
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

        $this->getDatabase()->table('employee')->insertMultiple(
            ['id', 'name_column', 'age'],
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

        $this->getDatabase()->table('programator')->insertMultiple(
            ['id', 'language'],
            [
                self::PROGRAMATOR_2,
                self::PROGRAMATOR_4,
            ]
        );

        $this->getDatabase()->table('manager')->insertMultiple(
            ['id', 'rank'],
            [
                self::MANAGER_1,
                self::MANAGER_3,
            ]
        );
    }

    protected function getSchemaArray(): array
    {
        return [
            Employee::class => [
                SchemaInterface::ROLE        => 'employee',
                SchemaInterface::MAPPER      => Mapper::class,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'employee',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'name' => 'name_column', 'age'],
                SchemaInterface::TYPECAST    => ['id' => 'int', 'age' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
            Engineer::class => [
                SchemaInterface::ROLE        => 'engineer',
                SchemaInterface::MAPPER      => Mapper::class,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'engineer',
                SchemaInterface::PARENT      => 'employee',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'level'],
                SchemaInterface::TYPECAST    => ['id' => 'int', 'level' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
            Programator::class => [
                SchemaInterface::ROLE        => 'programator',
                SchemaInterface::MAPPER      => Mapper::class,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'programator',
                SchemaInterface::PARENT      => 'engineer',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'language'],
                SchemaInterface::TYPECAST    => ['id' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
            Manager::class => [
                SchemaInterface::ROLE        => 'manager',
                SchemaInterface::MAPPER      => Mapper::class,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'manager',
                SchemaInterface::PARENT      => 'employee',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'rank'],
                SchemaInterface::TYPECAST    => ['id' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
        ];
    }

    // Select

    public function testSelectEmployeeAllData(): void
    {
        $selector = new Select($this->orm, Employee::class);

        $this->assertEquals(static::EMPLOYEE_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEmployeeDataFirst(): void
    {
        $selector = (new Select($this->orm, Employee::class))->limit(1);

        $this->assertEquals(static::EMPLOYEE_1_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectEngineerAllData(): void
    {
        $selector = (new Select($this->orm, Engineer::class));

        $this->assertEquals(static::ENGINEER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEngineerDataFirst(): void
    {
        $this->logger->display();

        $selector = (new Select($this->orm, Engineer::class))->limit(1);

        $this->assertEquals(static::ENGINEER_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectProgramatorAllData(): void
    {
        $selector = (new Select($this->orm, Programator::class));

        $this->assertEquals(static::PROGRAMATOR_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectProgramatorDataFirst(): void
    {
        $selector = (new Select($this->orm, Programator::class))->limit(1);

        $this->assertEquals(static::PROGRAMATOR_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectManagerAllData(): void
    {
        $selector = (new Select($this->orm, Manager::class));

        $this->assertEquals(static::MANAGER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectManagerDataFirst(): void
    {
        $selector = (new Select($this->orm, Manager::class))->limit(1);

        $this->assertEquals(static::MANAGER_1_LOADED, $selector->fetchData()[0]);
    }

    // Persist

    public function testProgramatorNoChanges(): void
    {
        $programator = (new Select($this->orm, Programator::class))->wherePK(2)->fetchOne();

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);
    }

    public function testChangeAndPersistProgramator(): void
    {
        /** @var Programator $programator */
        $programator = (new Select($this->orm, Programator::class))->wherePK(2)->fetchOne();
        $programator->language = 'Kotlin';

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);

        /** @var Programator $programator */
        $programator = (new Select($this->orm->withHeap(new Heap()), Programator::class))->wherePK(2)->fetchOne();
        $this->assertSame('Kotlin', $programator->language);
    }

    public function testChangeParentsFieldsAndPersistProgramator(): void
    {
        /** @var Programator $programator */
        $programator = (new Select($this->orm, Programator::class))->wherePK(2)->fetchOne();
        $programator->language = 'Kotlin';
        $programator->level = 99;
        $programator->name = 'Thomas';

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);

        /** @var Programator $programator */
        $programator = (new Select($this->orm->withHeap(new Heap()), Programator::class))->wherePK(2)->fetchOne();
        $this->assertSame('Kotlin', $programator->language);
        $this->assertSame(99, $programator->level);
        $this->assertSame('Thomas', $programator->name);
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

        /** @var Programator $programator */
        $programator = (new Select($this->orm->withHeap(new Heap()), Programator::class))
            ->wherePK($programator->id)
            ->fetchOne();
        $this->assertSame('Merlin', $programator->name);
        $this->assertSame(50, $programator->level);
        $this->assertSame('VanillaJS', $programator->language);
    }

    public function testRemoveEngineer(): void
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
        $this->assertNotNull((new Select($this->orm, Employee::class))->wherePK(2)->fetchOne());
    }
}
