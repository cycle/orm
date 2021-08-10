<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Inheritance\JTI\Mapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Inheritance\JTI\WithRelationsTest;

abstract class WithRelationsStdMapperTest extends WithRelationsTest
{
    protected const DEFAULT_MAPPER = StdMapper::class;

    protected function getSchemaArray(): array
    {
        $schema = parent::getSchemaArray();
        foreach ($schema as &$subSchema) {
            unset($subSchema[SchemaInterface::ENTITY]);
        }
        return $schema;
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

    public function testSelectEngineerEntityFirstWithInheritance(): void
    {
        $this->markTestSkipped('Should not check entity class.');
    }
}
