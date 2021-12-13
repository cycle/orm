<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\ORM;

use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Registry\IndexProviderInterface;
use Cycle\ORM\Registry\MapperProviderInterface;
use Cycle\ORM\Registry\RelationProviderInterface;
use Cycle\ORM\Registry\RepositoryProviderInterface;
use Cycle\ORM\Registry\SourceProviderInterface;
use Cycle\ORM\Registry\TypecastProviderInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use WeakMap;
use WeakReference;

abstract class MemoryTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();
        // Load all relations instances
    }

    public function testORMCloneGarbage(): void
    {
        $orm = $this->createORM();

        $link = WeakReference::create($orm);

        $orm = $this->orm->with(factory: new Factory($this->dbal));

        $this->assertNull($link->get());
    }

    public function testORMUnsetGarbage(): void
    {
        $orm = $this->createORM();

        $link = WeakReference::create($orm);

        unset($orm);

        $this->assertNull($link->get());
    }

    public function testORMWithLoadedEntityRegistryUnsetAndCollectGarbageCycles(): void
    {
        $orm = $this->createORM();
        $map = $this->collectReferences($orm);

        // $this->assertCount(17, $map);
        unset($orm);
        \gc_collect_cycles();

        $this->assertCount(0, $map);
    }

    public function testORMWithLoadedEntityRegistryUnsetWithoutGarbageCyclesCollecting(): void
    {
        $orm = $this->createORM();
        $map = $this->collectReferences($orm);

        // $this->assertCount(17, $map);
        unset($orm);

        $this->assertCount(0, $map);
    }

    private function createORM(): ORMInterface
    {
        $schema = new Schema(include __DIR__ . '/schema.php');
        return new ORM(new Factory($this->dbal), $schema);
    }

    private function collectReferences(ORMInterface $orm): WeakMap
    {
        $map = new WeakMap();
        $schema = $orm->getSchema();
        \assert($schema::class === Schema::class);

        $indexProvider = $orm->getProvider(IndexProviderInterface::class);
        $mapperProvider = $orm->getProvider(MapperProviderInterface::class);
        $relationProvider = $orm->getProvider(RelationProviderInterface::class);
        $repositoryProvider = $orm->getProvider(RepositoryProviderInterface::class);
        $sourceProvider = $orm->getProvider(SourceProviderInterface::class);
        $typecastProvider = $orm->getProvider(TypecastProviderInterface::class);

        foreach ($schema->toArray() as $role => $roleSchema) {
            $map[$mapperProvider->getMapper($role)] = true;
            $map[$relationProvider->getRelationMap($role)] = true;
            // $map[$repositoryProvider->getRepository($role)] = true;
            $map[$sourceProvider->getSource($role)] = true;
            $map[$typecastProvider->getTypecast($role)] = true;
        }
        $map[$indexProvider] = true;
        $map[$mapperProvider] = true;
        $map[$relationProvider] = true;
        $map[$repositoryProvider] = true;
        $map[$sourceProvider] = true;
        $map[$typecastProvider] = true;
        $map[$orm] = true;

        return $map;
    }
}
