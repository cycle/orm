<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\ORM;

use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Service\IndexProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\Service\RepositoryProviderInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Service\TypecastProviderInterface;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use WeakMap;
use WeakReference;

abstract class MemoryTest extends BaseTest
{
    use TableTrait;

    private const ACTION_CLONE = 'clone';
    private const ACTION_UNSET = 'unset';
    private const ACTION_WITH = 'with';

    public function setUp(): void
    {
        parent::setUp();
        // Load all relations instances
    }

    // With `Collect Garbage Cycles`

    public function configProvider(): iterable
    {
        return [
            # With `Collect Garbage Cycles`
            [self::ACTION_WITH, 'garbage' => true, 'warmupOrm' => true, 'loadLinks' => false, 'loadRoles' => false],
            [self::ACTION_UNSET, 'garbage' => true, 'warmupOrm' => true, 'loadLinks' => false, 'loadRoles' => false],
            // [self::ACTION_UNSET, 'garbage' => true, 'warmupOrm' => true, 'loadLinks' => true, 'loadRoles' => false],
            // [self::ACTION_UNSET, 'garbage' => true, 'warmupOrm' => true, 'loadLinks' => true, 'loadRoles' => true],
            # Without `Collect Garbage Cycles`
            [self::ACTION_WITH, 'garbage' => false, 'warmupOrm' => true, 'loadLinks' => false, 'loadRoles' => false],
            [self::ACTION_UNSET, 'garbage' => false, 'warmupOrm' => true, 'loadLinks' => false, 'loadRoles' => false],
            // [self::ACTION_UNSET, 'garbage' => false, 'warmupOrm' => true, 'loadLinks' => true, 'loadRoles' => false],
            // [self::ACTION_UNSET, 'garbage' => false, 'warmupOrm' => true, 'loadLinks' => true, 'loadRoles' => true],

            # The same but without warming up

            # With `Collect Garbage Cycles`
            [self::ACTION_WITH, 'garbage' => true, 'warmupOrm' => false, 'loadLinks' => false, 'loadRoles' => false],
            [self::ACTION_UNSET, 'garbage' => true, 'warmupOrm' => false, 'loadLinks' => false, 'loadRoles' => false],
            [self::ACTION_UNSET, 'garbage' => true, 'warmupOrm' => false, 'loadLinks' => true, 'loadRoles' => false],
            // [self::ACTION_UNSET, 'garbage' => true, 'warmupOrm' => false, 'loadLinks' => true, 'loadRoles' => true],
            # Without `Collect Garbage Cycles`
            // [self::ACTION_WITH, 'garbage' => false, 'warmupOrm' => false, 'loadLinks' => false, 'loadRoles' => false],
            // [self::ACTION_UNSET, 'garbage' => false, 'warmupOrm' => false, 'loadLinks' => false, 'loadRoles' => false],
            // [self::ACTION_UNSET, 'garbage' => false, 'warmupOrm' => false, 'loadLinks' => true, 'loadRoles' => false],
            // [self::ACTION_UNSET, 'garbage' => false, 'warmupOrm' => false, 'loadLinks' => true, 'loadRoles' => true],
        ];
    }

    /**
     * @dataProvider configProvider()
     */
    public function testOrm(string $action, bool $garbage, bool $warmupOrm, bool $loadLinks, bool $loadRoles): void
    {
        // Create ORM
        $orm = $this->createORM();
        // Preload all ORM services
        $warmupOrm and $orm->loadServices();
        // Collect weak references
        $loadLinks and $map = $this->collectReferences($orm, $loadRoles);
        // Create main ORM reference
        $link = WeakReference::create($orm);

        // Do main action
        switch ($action) {
            case self::ACTION_WITH:
                $orm = $this->orm->with(factory: new Factory($this->dbal));
                break;
            case self::ACTION_UNSET:
                unset($orm);
                break;
        }

        // Collect cyclic references
        $garbage and \gc_collect_cycles();
        // check weak references
        $loadLinks and $this->assertCount(0, $map);
        // Check main orm reference
        $this->assertTrue($link->get() === null);
    }

    // Support

    private function createORM(): ORM
    {
        $schema = new Schema(include __DIR__ . '/schema.php');
        return new ORM(new Factory($this->dbal), $schema);
    }

    private function collectReferences(ORMInterface $orm, bool $loadRoles): WeakMap
    {
        $map = new WeakMap();
        $schema = $orm->getSchema();
        \assert($schema::class === Schema::class);

        $entityFactory = $orm->getService(EntityFactoryInterface::class);
        $indexProvider = $orm->getService(IndexProviderInterface::class);
        $mapperProvider = $orm->getService(MapperProviderInterface::class);
        $relationProvider = $orm->getService(RelationProviderInterface::class);
        $repositoryProvider = $orm->getService(RepositoryProviderInterface::class);
        $sourceProvider = $orm->getService(SourceProviderInterface::class);
        $typecastProvider = $orm->getService(TypecastProviderInterface::class);

        if ($loadRoles) {
            foreach ($schema->toArray() as $role => $roleSchema) {
                $map[$mapperProvider->getMapper($role)] = true;
                $map[$relationProvider->getRelationMap($role)] = true;
                $map[$repositoryProvider->getRepository($role)] = true;
                $map[$sourceProvider->getSource($role)] = true;
                $map[$typecastProvider->getTypecast($role)] = true;
            }
        }
        $map[$entityFactory] = true;
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
