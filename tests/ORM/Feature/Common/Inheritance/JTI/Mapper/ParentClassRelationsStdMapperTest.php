<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\JTI\Mapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\Inheritance\JTI\Relation\ParentClassRelationsTest;
use Cycle\Testing\Database\Assert\QueryAssert;
use Testo\Assert;
use Testo\Skip;
use Testo\Test;

class ParentClassRelationsStdMapperTest extends ParentClassRelationsTest
{
    protected const DEFAULT_MAPPER = StdMapper::class;

    #[Test]
    public function testCreateProgramator(): void
    {
        $programator = $this->orm->make(static::PROGRAMATOR_ROLE);
        $programator->name = 'Merlin';
        $programator->level = 50;
        $programator->language = 'VanillaJS';

        QueryAssert::writes(3, fn() => $this->save($programator));

        QueryAssert::writes(0, fn() => $this->save($programator));

        $programator = (new Select($this->orm->withHeap(new Heap()), static::PROGRAMATOR_ROLE))
            ->wherePK($programator->id)
            ->fetchOne();
        Assert::same($programator->name, 'Merlin');
        Assert::same($programator->level, 50);
        Assert::same($programator->language, 'VanillaJS');
    }

    #[Test]
    #[Skip('Skip for classless mapper.')]
    public function testSelectEngineerEntityFirstWithInheritance(): void {}

    #[Test]
    #[Skip('Skip for classless mapper.')]
    public function testSelectEmployeeHierarchyByPK(): void {}

    protected function getSchemaArray(): array
    {
        $schema = parent::getSchemaArray();
        foreach ($schema as &$subSchema) {
            unset($subSchema[SchemaInterface::ENTITY]);
        }
        return $schema;
    }
}
