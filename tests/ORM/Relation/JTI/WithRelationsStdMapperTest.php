<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;

abstract class WithRelationsStdMapperTest extends WithRelationsTest
{
    protected const DEFAULT_MAPPER = StdMapper::class;

    protected function getSchemaArray(): array
    {
        return [
            static::EMPLOYEE_ROLE => [
                SchemaInterface::MAPPER      => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'employee',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'name', 'age', 'book_id'],
                SchemaInterface::TYPECAST    => ['id' => 'int', 'book_id' => 'int', 'age' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [
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
            static::ENGINEER_ROLE => [
                SchemaInterface::MAPPER      => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'engineer',
                SchemaInterface::PARENT      => 'employee',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'level', 'tech_book_id'],
                SchemaInterface::TYPECAST    => ['id' => 'int', 'level' => 'int', 'tech_book_id' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [
                    'tech_book' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => 'book',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'tech_book_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'tools' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => 'tool',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::NULLABLE  => false,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'engineer_id',
                        ],
                    ],
                ],
            ],
            'programator' => [
                SchemaInterface::MAPPER      => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'programator',
                SchemaInterface::PARENT      => 'engineer',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'language'],
                SchemaInterface::TYPECAST    => ['id' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
            'manager' => [
                SchemaInterface::MAPPER      => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'manager',
                SchemaInterface::PARENT      => 'employee',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'rank'],
                SchemaInterface::TYPECAST    => ['id' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
            'book' => [
                SchemaInterface::MAPPER      => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'book',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'title'],
                SchemaInterface::TYPECAST    => ['id' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
            'tool' => [
                SchemaInterface::MAPPER      => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE    => 'default',
                SchemaInterface::TABLE       => 'tool',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS     => ['id', 'title', 'engineer_id'],
                SchemaInterface::TYPECAST    => ['id' => 'int', 'engineer_id' => 'int'],
                SchemaInterface::SCHEMA      => [],
                SchemaInterface::RELATIONS   => [],
            ],
        ];
    }

    public function testLoadProgramatorAndCheckParentsRelations(): void
    {
        $entity = (new Select($this->orm, static::PROGRAMATOR_ROLE))->wherePK(2)->fetchOne();

        $this->assertNotNull($entity->book);
        $this->assertNotNull($entity->tech_book);
    }

    public function testCreateProgramator(): void
    {
        $programator = $this->orm->make(static::PROGRAMATOR_ROLE);
        $programator->name = 'Merlin';
        $programator->level = 50;
        $programator->language = 'VanillaJS';

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($programator);
        $this->assertNumWrites(0);

        $programator = (new Select($this->orm->withHeap(new Heap()), static::PROGRAMATOR_ROLE))
            ->wherePK($programator->id)
            ->fetchOne();
        $this->assertSame('Merlin', $programator->name);
        $this->assertSame(50, $programator->level);
        $this->assertSame('VanillaJS', $programator->language);
    }
}
