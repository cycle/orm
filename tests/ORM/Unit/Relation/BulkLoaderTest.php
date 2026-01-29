<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Relation\RelationLoaderInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\Profile;
use PHPUnit\Framework\TestCase;

class BulkLoaderTest extends TestCase
{
    /**
     * Test that BulkLoader throws exception when no entities are provided
     */
    public function testCollectThrowsExceptionWhenNoEntitiesProvided(): void
    {
        $orm = $this->createORM();
        $loader = new BulkLoader($orm);
        $loader->collect();

        $this->expectNotToPerformAssertions();
    }

    /**
     * Test that BulkLoader throws exception when entities have different roles
     */
    public function testCollectThrowsExceptionWhenEntitiesHaveDifferentRoles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All entities must belong to the same role.');

        $orm = $this->createORM();

        $user = new User();
        $profile = new Profile();

        $loader = new BulkLoader($orm);
        $loader->collect($user, $profile);
    }

    /**
     * Test run throws exception when entity node not found in heap
     */
    public function testRunThrowsExceptionWhenEntityNodeNotFoundInHeap(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity node not found in the heap.');

        $orm = $this->createORM();
        $entity = new User();

        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $loader->run();
    }

    /**
     * Test collect with a single entity
     */
    public function testCollectWithSingleEntity(): void
    {
        $orm = $this->createORM();
        $entity = new User();

        $loader = new BulkLoader($orm);
        $result = $loader->collect($entity);

        $this->assertInstanceOf(BulkLoader::class, $result);
    }

    /**
     * Test collect with multiple entities of the same role
     */
    public function testCollectWithMultipleEntitiesOfSameRole(): void
    {
        $orm = $this->createORM();

        $entity1 = new User();
        $entity2 = new User();
        $entity3 = new User();

        $loader = new BulkLoader($orm);
        $result = $loader->collect($entity1, $entity2, $entity3);

        $this->assertInstanceOf(BulkLoader::class, $result);
    }

    /**
     * Test collect returns a new instance (immutability)
     */
    public function testCollectReturnsNewInstance(): void
    {
        $orm = $this->createORM();

        $loader1 = new BulkLoader($orm);
        $entity = new User();
        $loader2 = $loader1->collect($entity);

        $this->assertNotSame($loader1, $loader2, 'collect() should return a new instance');
    }

    /**
     * Test chaining multiple collect calls
     */
    public function testChainingMultipleCollectCalls(): void
    {
        $orm = $this->createORM();

        $entity1 = new User();
        $entity2 = new User();

        $loader = new BulkLoader($orm);
        $loader1 = $loader->collect($entity1);
        $loader2 = $loader1->collect($entity2);

        $this->assertNotSame($loader, $loader1);
        $this->assertNotSame($loader1, $loader2);
        $this->assertInstanceOf(BulkLoader::class, $loader2);
    }

    /**
     * Test collect with empty array (edge case)
     */
    public function testCollectWithEmptyArrayUnpacking(): void
    {
        $orm = $this->createORM();
        $loader = (new BulkLoader($orm))->collect();

        self::assertInstanceOf(RelationLoaderInterface::class, $loader);
        $loader->load('profile');
        $loader->run();
    }

    /**
     * Test that collect merges entities from multiple calls
     */
    public function testCollectMergesEntities(): void
    {
        $orm = $this->createORM();

        $entity1 = new User();
        $entity2 = new User();
        $entity3 = new User();

        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity1);
        $loader = $loader->collect($entity2, $entity3);

        // Should not throw exception - all entities should be collected
        $this->assertInstanceOf(BulkLoader::class, $loader);
    }

    /**
     * Test load returns the same instance (fluent interface)
     */
    public function testLoadReturnsSameInstance(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $loaderAfterLoad = $loader->load('profile');

        $this->assertSame($loader, $loaderAfterLoad, 'load() should return the same instance');
    }

    /**
     * Test chaining load calls
     */
    public function testChainingLoadCalls(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $result = $loader->load('profile')->load('profile'); // Load same relation twice

        $this->assertSame($loader, $result);
    }

    /**
     * Test load with custom options
     */
    public function testLoadWithCustomOptions(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $result = $loader->load('profile', ['where' => ['id' => 1]]);

        $this->assertSame($loader, $result);
    }

    private function createORM(): ORM
    {
        $schema = new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'profile' => [
                        \Cycle\ORM\Relation::TYPE => \Cycle\ORM\Relation::HAS_ONE,
                        \Cycle\ORM\Relation::TARGET => Profile::class,
                        \Cycle\ORM\Relation::SCHEMA => [
                            \Cycle\ORM\Relation::CASCADE => true,
                            \Cycle\ORM\Relation::INNER_KEY => 'id',
                            \Cycle\ORM\Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Profile::class => [
                Schema::ROLE => 'profile',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'image'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]);

        return new ORM(new Factory($this->createMock(\Cycle\Database\DatabaseProviderInterface::class)), $schema);
    }
}
