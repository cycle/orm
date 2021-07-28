<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Employee;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Engineer;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Manager;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Programator;
use Cycle\ORM\Tests\Relation\JTI\Trait\SelectTrait;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class SimpleTest extends JtiBaseTest
{
    use TableTrait;
    use SelectTrait;

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

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
        $this->logger->display();
    }

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

    public function testRemoveProgramator(): void
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

    protected function getSchemaArray(): array
    {
        return [
            Employee::class => [
                Schema::ROLE        => 'employee',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'employee',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'name' => 'name_column', 'age'],
                Schema::TYPECAST    => ['id' => 'int', 'age' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
            ],
            Engineer::class => [
                Schema::ROLE        => 'engineer',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'engineer',
                Schema::PARENT      => 'employee',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'level'],
                Schema::TYPECAST    => ['id' => 'int', 'level' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
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
        ];
    }
}
