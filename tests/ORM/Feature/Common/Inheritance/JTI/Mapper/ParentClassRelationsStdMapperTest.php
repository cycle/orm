<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\JTI\Mapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\Inheritance\JTI\Relation\ParentClassRelationsTest;

abstract class ParentClassRelationsStdMapperTest extends ParentClassRelationsTest
{
    protected const DEFAULT_MAPPER = StdMapper::class;

    #[\Testo\Test]
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
        \Testo\Assert::same($programator->name, 'Merlin');
        \Testo\Assert::same($programator->level, 50);
        \Testo\Assert::same($programator->language, 'VanillaJS');
    }

    #[\Testo\Test]
    #[\Testo\Skip('Skip for classless mapper.')]
    public function testSelectEngineerEntityFirstWithInheritance(): void
    {
    }

    #[\Testo\Test]
    #[\Testo\Skip('Skip for classless mapper.')]
    public function testSelectEmployeeHierarchyByPK(): void
    {
    }

    protected function getSchemaArray(): array
    {
        $schema = parent::getSchemaArray();
        foreach ($schema as &$subSchema) {
            unset($subSchema[SchemaInterface::ENTITY]);
        }
        return $schema;
    }
}
