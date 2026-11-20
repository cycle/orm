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
use Cycle\Testing\Database\Memory;
use Cycle\Testing\Database\Schema\TableBuilder;
use Testo\Assert;
use Testo\Assert\ExpectNoAssertions;
use Testo\Expect;
use Testo\Test;

#[Test]
final class BulkLoaderTest
{
    /**
     * Test that BulkLoader throws exception when no entities are provided
     */
    #[ExpectNoAssertions]
    public function testCollectThrowsExceptionWhenNoEntitiesProvided(): void
    {
        $orm = $this->createORM();
        $loader = new BulkLoader($orm);
        $loader->collect();

    }

    /**
     * Test that BulkLoader throws exception when entities have different roles
     */
    public function testCollectThrowsExceptionWhenEntitiesHaveDifferentRoles(): void
    {
        Expect::exception(\InvalidArgumentException::class)->withMessageContaining('All entities must belong to the same role.');

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
        Expect::exception(\LogicException::class)->withMessageContaining('Entity node not found in the heap.');

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

        Assert::instanceOf($result, BulkLoader::class);
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

        Assert::instanceOf($result, BulkLoader::class);
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

        Assert::notSame($loader2, $loader1, 'collect() should return a new instance');
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

        Assert::notSame($loader1, $loader);
        Assert::notSame($loader2, $loader1);
        Assert::instanceOf($loader2, BulkLoader::class);
    }

    /**
     * Test collect with empty array (edge case)
     */
    public function testCollectWithEmptyArrayUnpacking(): void
    {
        $orm = $this->createORM();
        $loader = (new BulkLoader($orm))->collect();

        Assert::instanceOf($loader, RelationLoaderInterface::class);
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
        Assert::instanceOf($loader, BulkLoader::class);
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

        Assert::same($loaderAfterLoad, $loader, 'load() should return the same instance');
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

        Assert::same($result, $loader);
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

        Assert::same($result, $loader);
    }

    /**
     * Test load with LoadOptions DTO
     */
    public function testLoadWithLoadOptionsDTO(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $result = $loader->load('profile', new LoadOptions());

        Assert::same($result, $loader);
    }

    /**
     * Test load with HasOneLoadOptions DTO
     */
    public function testLoadWithHasOneLoadOptionsDTO(): void
    {
        $orm = $this->createORM();

        $entity = new User();
        $loader = new BulkLoader($orm);
        $loader = $loader->collect($entity);
        $result = $loader->load('profile', new HasOneLoadOptions(
            where: ['id' => 1],
        ));

        Assert::same($result, $loader);
    }

    /**
     * Test empty collect returns loader that accepts LoadOptions DTO
     */
    public function testEmptyCollectLoadAcceptsLoadOptionsDTO(): void
    {
        $orm = $this->createORM();
        $loader = (new BulkLoader($orm))->collect();

        Assert::instanceOf($loader, RelationLoaderInterface::class);
        $result = $loader->load('profile', new HasOneLoadOptions());
        Assert::same($result, $loader);

        $loader->run();
    }

    /**
     * BulkLoader should handle Stringable PK values when typecast is one-directional.
     *
     * Scenario: OneWayUuidTypecast implements only CastableInterface (cast: string→UuidPrimaryKey),
     * but NOT UncastableInterface. So mapper->uncast() returns data with UuidPrimaryKey objects.
     * BulkLoader::indexEntity() must handle Stringable objects instead of rejecting them via is_scalar().
     */
    #[ExpectNoAssertions]
    public function testRunWithStringablePkAndOneWayTypecast(): void
    {
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

        // The relation query runs against an empty in-memory table.
        $dbal = Memory::manager();
        (new TableBuilder($dbal->database()))->makeTable('profile', [
            'id' => 'primary',
            'user_id' => 'string',
            'image' => 'string',
        ]);
        $orm = new ORM(new Factory($dbal), $schema);

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
