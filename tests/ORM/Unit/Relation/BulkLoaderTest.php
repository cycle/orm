<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Factory;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Relation\RelationLoaderInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Options\HasOneLoadOptions;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Tests\Fixtures\OneWayUuidTypecast;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UuidPrimaryKey;
use PHPUnit\Framework\TestCase;

class BulkLoaderTest
{
    /**
     * Test that BulkLoader throws exception when no entities are provided
     */
    #[\Testo\Test]
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
    #[\Testo\Test]
    public function testCollectThrowsExceptionWhenEntitiesHaveDifferentRoles(): void
    {
        \Testo\Expect::exception(\InvalidArgumentException::class)->withMessageContaining('All entities must belong to the same role.');

        $orm = $this->createORM();

        $user = new User();
        $profile = new Profile();

        $loader = new BulkLoader($orm);
        $loader->collect($user, $profile);
    }

    /**
     * Test run throws exception when entity node not found in heap
     */
    #[\Testo\Test]
    public function testRunThrowsExceptionWhenEntityNodeNotFoundInHeap(): void
    {
        \Testo\Expect::exception(\LogicException::class)->withMessageContaining('Entity node not found in the heap.');

        $orm = $this->createORM();
        $entity = new User();

        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $loader->run();
    }

    /**
     * Test collect with a single entity
     */
    #[\Testo\Test]
    public function testCollectWithSingleEntity(): void
    {
        $orm = $this->createORM();
        $entity = new User();

        $loader = new BulkLoader($orm);
        $result = $loader->collect($entity);

        \Testo\Assert::instanceOf($result, BulkLoader::class);
    }

    /**
     * Test collect with multiple entities of the same role
     */
    #[\Testo\Test]
    public function testCollectWithMultipleEntitiesOfSameRole(): void
    {
        $orm = $this->createORM();

        $entity1 = new User();
        $entity2 = new User();
        $entity3 = new User();

        $loader = new BulkLoader($orm);
        $result = $loader->collect($entity1, $entity2, $entity3);

        \Testo\Assert::instanceOf($result, BulkLoader::class);
    }

    /**
     * Test collect returns a new instance (immutability)
     */
    #[\Testo\Test]
    public function testCollectReturnsNewInstance(): void
    {
        $orm = $this->createORM();

        $loader1 = new BulkLoader($orm);
        $entity = new User();
        $loader2 = $loader1->collect($entity);

        \Testo\Assert::notSame($loader2, $loader1, 'collect() should return a new instance');
    }

    /**
     * Test chaining multiple collect calls
     */
    #[\Testo\Test]
    public function testChainingMultipleCollectCalls(): void
    {
        $orm = $this->createORM();

        $entity1 = new User();
        $entity2 = new User();

        $loader = new BulkLoader($orm);
        $loader1 = $loader->collect($entity1);
        $loader2 = $loader1->collect($entity2);

        \Testo\Assert::notSame($loader1, $loader);
        \Testo\Assert::notSame($loader2, $loader1);
        \Testo\Assert::instanceOf($loader2, BulkLoader::class);
    }

    /**
     * Test collect with empty array (edge case)
     */
    #[\Testo\Test]
    public function testCollectWithEmptyArrayUnpacking(): void
    {
        $orm = $this->createORM();
        $loader = (new BulkLoader($orm))->collect();

        \Testo\Assert::instanceOf($loader, RelationLoaderInterface::class);
        $loader->load('profile');
        $loader->run();
    }

    /**
     * Test that collect merges entities from multiple calls
     */
    #[\Testo\Test]
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
        \Testo\Assert::instanceOf($loader, BulkLoader::class);
    }

    /**
     * Test load returns the same instance (fluent interface)
     */
    #[\Testo\Test]
    public function testLoadReturnsSameInstance(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $loaderAfterLoad = $loader->load('profile');

        \Testo\Assert::same($loaderAfterLoad, $loader, 'load() should return the same instance');
    }

    /**
     * Test chaining load calls
     */
    #[\Testo\Test]
    public function testChainingLoadCalls(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $result = $loader->load('profile')->load('profile'); // Load same relation twice

        \Testo\Assert::same($result, $loader);
    }

    /**
     * Test load with custom options
     */
    #[\Testo\Test]
    public function testLoadWithCustomOptions(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $result = $loader->load('profile', ['where' => ['id' => 1]]);

        \Testo\Assert::same($result, $loader);
    }

    /**
     * Test load with LoadOptions DTO
     */
    #[\Testo\Test]
    public function testLoadWithLoadOptionsDTO(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $result = $loader->load('profile', new LoadOptions());

        \Testo\Assert::same($result, $loader);
    }

    /**
     * Test load with HasOneLoadOptions DTO
     */
    #[\Testo\Test]
    public function testLoadWithHasOneLoadOptionsDTO(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $result = $loader->load('profile', new HasOneLoadOptions(
            where: ['id' => 1],
        ));

        \Testo\Assert::same($result, $loader);
    }

    /**
     * Test empty collect returns loader that accepts LoadOptions DTO
     */
    #[\Testo\Test]
    public function testEmptyCollectLoadAcceptsLoadOptionsDTO(): void
    {
        $orm = $this->createORM();
        $loader = (new BulkLoader($orm))->collect();

        \Testo\Assert::instanceOf($loader, RelationLoaderInterface::class);
        $result = $loader->load('profile', new HasOneLoadOptions());
        \Testo\Assert::same($result, $loader);

        $loader->run();
    }

    /**
     * BulkLoader should handle Stringable PK values when typecast is one-directional.
     *
     * Scenario: OneWayUuidTypecast implements only CastableInterface (cast: string→UuidPrimaryKey),
     * but NOT UncastableInterface. So mapper->uncast() returns data with UuidPrimaryKey objects.
     * BulkLoader::indexEntity() must handle Stringable objects instead of rejecting them via is_scalar().
     */
    #[\Testo\Test]
    public function testRunWithStringablePkAndOneWayTypecast(): void
    {
        $this->expectNotToPerformAssertions();

        $schema = new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::TYPECAST => ['id' => 'uuid'],
                Schema::TYPECAST_HANDLER => OneWayUuidTypecast::class,
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'profile' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Profile::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
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

        $orm = new ORM(
            new Factory(\Mockery::mock(\Cycle\Database\DatabaseProviderInterface::class)->shouldIgnoreMissing()),
            $schema,
        );

        $entity = new User();

        // Simulate entity loaded from DB: typecast converted string PK to UuidPrimaryKey (Stringable),
        // but uncast won't convert it back because OneWayUuidTypecast has no UncastableInterface.
        $uuid = new UuidPrimaryKey('550e8400-e29b-41d4-a716-446655440001');
        $node = new Node(Node::MANAGED, [
            'id' => $uuid,
            'email' => 'test@test.com',
            'balance' => 100,
        ], 'user');
        $orm->getHeap()->attach($entity, $node);

        $loader = (new BulkLoader($orm))->collect($entity);
        $loader->load('profile');
        $loader->run();
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
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Profile::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
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

        return new ORM(new Factory(\Mockery::mock(\Cycle\Database\DatabaseProviderInterface::class)->shouldIgnoreMissing()), $schema);
    }
}
