<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\LazyGhost;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Mapper\LazyGhost\LazyGhostEntityFactory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\ActiveRelationInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\FinalEntity;
use Cycle\ORM\Tests\Fixtures\SimpleEntity;
use PHPUnit\Framework\TestCase;

class LazyGhostEntityFactoryTest extends TestCase
{
    private LazyGhostEntityFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new LazyGhostEntityFactory();
    }

    public function testCreateReturnsInstanceOfRequestedClass(): void
    {
        $entity = $this->factory->create($this->emptyRelationMap(), SimpleEntity::class);

        $this->assertInstanceOf(SimpleEntity::class, $entity);
    }

    public function testCreateWorksWithFinalClass(): void
    {
        $entity = $this->factory->create($this->emptyRelationMap(), FinalEntity::class);

        $this->assertInstanceOf(FinalEntity::class, $entity);
    }

    public function testCreateReturnsUninitializedLazyGhost(): void
    {
        $entity = $this->factory->create($this->emptyRelationMap(), SimpleEntity::class);

        $reflection = new \ReflectionClass($entity);

        $this->assertTrue($reflection->isUninitializedLazyObject($entity));
    }

    public function testCreateDoesNotCallConstructor(): void
    {
        $entity = $this->factory->create($this->emptyRelationMap(), FinalEntity::class);

        $reflection = new \ReflectionClass($entity);

        // uuid is set in constructor with default ''; lazy ghost should skip it
        $this->assertTrue($reflection->isUninitializedLazyObject($entity));
        $this->assertFalse($reflection->getProperty('uuid')->isInitialized($entity));
    }

    public function testUpgradeHydratesScalarProperties(): void
    {
        $relMap = $this->emptyRelationMap();
        $entity = $this->factory->create($relMap, SimpleEntity::class);

        $entity = $this->factory->upgrade($relMap, $entity, [
            'id' => 42,
            'name' => 'Test',
            'description' => 'A description',
            'sortOrder' => 5,
        ]);

        $this->assertSame(42, $entity->id);
        $this->assertSame('Test', $entity->name);
        $this->assertSame('A description', $entity->description);
        $this->assertSame(5, $entity->sortOrder);
    }

    public function testUpgradeHydratesFinalEntityWithPrivateSetProperties(): void
    {
        $relMap = $this->emptyRelationMap();
        $entity = $this->factory->create($relMap, FinalEntity::class);

        $this->factory->upgrade($relMap, $entity, [
            'id' => 7,
            'title' => 'Hello',
            'slug' => 'hello-world',
        ]);

        $this->assertSame(7, $entity->id);
        $this->assertSame('Hello', $entity->title);
        $this->assertSame('hello-world', $entity->slug);
    }

    public function testUpgradeSkipsInitializedReadonlyProperty(): void
    {
        $relMap = $this->emptyRelationMap();
        $entity = $this->factory->create($relMap, FinalEntity::class);

        // First hydration sets readonly uuid
        $this->factory->upgrade($relMap, $entity, ['uuid' => 'first']);

        // Second hydration must not overwrite it
        $this->factory->upgrade($relMap, $entity, ['uuid' => 'second']);

        $this->assertSame('first', $entity->uuid);
    }

    public function testUpgradeStoresPendingRelationReference(): void
    {
        $ref = $this->createMock(ReferenceInterface::class);
        $relation = $this->createMock(ActiveRelationInterface::class);

        $relMap = $this->buildRelationMap(['role' => $relation]);
        $entity = $this->factory->create($relMap, SimpleEntity::class);

        // Upgrade with a ReferenceInterface value for a known relation
        $this->factory->upgrade($relMap, $entity, [
            'name' => 'Test',
            'role' => $ref,
        ]);

        // Entity is still lazy (has pending refs)
        $reflection = new \ReflectionClass($entity);
        $this->assertTrue($reflection->isUninitializedLazyObject($entity));
    }

    public function testUpgradeMarksAsInitializedWhenNoPendingRefs(): void
    {
        $relMap = $this->emptyRelationMap();
        $entity = $this->factory->create($relMap, SimpleEntity::class);

        $this->factory->upgrade($relMap, $entity, ['name' => 'Test']);

        $reflection = new \ReflectionClass($entity);
        $this->assertFalse($reflection->isUninitializedLazyObject($entity));
    }

    public function testExtractDataReturnsScalarValues(): void
    {
        $relMap = $this->emptyRelationMap();
        $entity = $this->factory->create($relMap, SimpleEntity::class);

        $this->factory->upgrade($relMap, $entity, [
            'id' => 1,
            'name' => 'Alice',
            'description' => null,
            'sortOrder' => 3,
        ]);

        $data = $this->factory->extractData($relMap, $entity);

        $this->assertSame(1, $data['id']);
        $this->assertSame('Alice', $data['name']);
        $this->assertNull($data['description']);
        $this->assertSame(3, $data['sortOrder']);
    }

    public function testExtractDataSkipsUninitializedProperties(): void
    {
        $relMap = $this->emptyRelationMap();
        $entity = $this->factory->create($relMap, FinalEntity::class);

        // Only hydrate id and title, leave uuid and slug uninitialized
        $this->factory->upgrade($relMap, $entity, [
            'id' => 10,
            'title' => 'Partial',
        ]);

        $data = $this->factory->extractData($relMap, $entity);

        $this->assertSame(10, $data['id']);
        $this->assertSame('Partial', $data['title']);
        $this->assertArrayNotHasKey('uuid', $data);
        $this->assertArrayNotHasKey('slug', $data);
    }

    public function testExtractDataExcludesRelationProperties(): void
    {
        $relation = $this->createMock(ActiveRelationInterface::class);
        $relMap = $this->buildRelationMap(['description' => $relation]);

        $entity = new SimpleEntity();
        $entity->id = 1;
        $entity->name = 'Bob';
        $entity->description = 'should be excluded';

        $data = $this->factory->extractData($relMap, $entity);

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('description', $data);
    }

    public function testExtractRelationsReturnsPendingReference(): void
    {
        $ref = $this->createMock(ReferenceInterface::class);
        $relation = $this->createMock(ActiveRelationInterface::class);

        $relMap = $this->buildRelationMap(['role' => $relation]);
        $entity = $this->factory->create($relMap, SimpleEntity::class);

        $this->factory->upgrade($relMap, $entity, ['role' => $ref]);

        $relations = $this->factory->extractRelations($relMap, $entity);

        $this->assertSame($ref, $relations['role']);
    }

    public function testExtractRelationsReturnsResolvedValue(): void
    {
        $relation = $this->createMock(ActiveRelationInterface::class);
        $relMap = $this->buildRelationMap(['description' => $relation]);

        $entity = new SimpleEntity();
        $entity->description = 'resolved value';

        $relations = $this->factory->extractRelations($relMap, $entity);

        $this->assertSame('resolved value', $relations['description']);
    }

    public function testExtractAllCombinesDataAndRelations(): void
    {
        $relation = $this->createMock(ActiveRelationInterface::class);
        $relMap = $this->buildRelationMap(['description' => $relation]);

        // Use a regular (non-lazy) entity to test the merge of data + relations
        $entity = new SimpleEntity();
        $entity->id = 5;
        $entity->name = 'Combined';
        $entity->description = 'relation-value';

        $all = $this->factory->extractAll($relMap, $entity);

        $this->assertSame(5, $all['id']);
        $this->assertSame('Combined', $all['name']);
        // description is in the relation map → comes from extractRelations, not extractData
        $this->assertSame('relation-value', $all['description']);
        // sortOrder is scalar data
        $this->assertSame(0, $all['sortOrder']);
    }

    public function testPropertyAccessTriggersResolutionOfPendingRefs(): void
    {
        $resolvedParent = new \stdClass();
        $resolvedParent->name = 'admin';

        $ref = $this->createMock(ReferenceInterface::class);
        $relation = $this->createMock(ActiveRelationInterface::class);
        $relation->method('resolve')->with($ref, true)->willReturn($resolvedParent);
        $relation->method('collect')->with($resolvedParent)->willReturn($resolvedParent);

        // Use 'parent' (?object) as a pseudo-relation on SimpleEntity
        $relMap = $this->buildRelationMap(['parent' => $relation]);
        $entity = $this->factory->create($relMap, SimpleEntity::class);

        // Only pass the relation ref — no scalar data
        $this->factory->upgrade($relMap, $entity, [
            'parent' => $ref,
        ]);

        $reflection = new \ReflectionClass($entity);
        $this->assertTrue($reflection->isUninitializedLazyObject($entity));

        // Access a property NOT set via setRawValueWithoutLazyInitialization
        // to trigger the lazy ghost initializer
        $sort = $entity->sortOrder;

        $this->assertFalse($reflection->isUninitializedLazyObject($entity));
        $this->assertSame(0, $sort);
        $this->assertSame($resolvedParent, $entity->parent);
    }

    private function emptyRelationMap(): RelationMap
    {
        return $this->buildRelationMap();
    }

    /**
     * @param array<string, ActiveRelationInterface> $relations
     */
    private function buildRelationMap(array $relations = []): RelationMap
    {
        $schemaRelations = [];
        foreach (\array_keys($relations) as $name) {
            $schemaRelations[$name] = [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'entity',
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::INNER_KEY => $name . '_id',
                    Relation::OUTER_KEY => 'id',
                ],
            ];
        }

        $ormFactory = $this->createMock(FactoryInterface::class);
        $ormFactory->method('relation')
            ->willReturnCallback(static fn($orm, $schema, $role, $name) => $relations[$name]);

        $orm = new ORM(
            $ormFactory,
            new Schema([
                'entity' => [
                    SchemaInterface::ENTITY => SimpleEntity::class,
                    SchemaInterface::MAPPER => Mapper::class,
                    SchemaInterface::DATABASE => 'default',
                    SchemaInterface::TABLE => 'entity',
                    SchemaInterface::PRIMARY_KEY => 'id',
                    SchemaInterface::COLUMNS => ['id'],
                    SchemaInterface::SCHEMA => [],
                    SchemaInterface::RELATIONS => $schemaRelations,
                ],
            ]),
        );

        return RelationMap::build($orm, 'entity');
    }
}
