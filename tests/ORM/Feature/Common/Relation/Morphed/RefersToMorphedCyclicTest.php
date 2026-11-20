<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Options;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\MorphedCyclic\EntityA;
use Cycle\ORM\Tests\Fixtures\MorphedCyclic\EntityB;
use Cycle\ORM\Tests\Fixtures\MorphedCyclic\MorphedInterface;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * Cyclic dependencies via {@see Relation::REFERS_TO_MORPHED}:
 *  - A > A: an entity points to itself through a morphed relation;
 *  - A > B > A: two entities point at each other through morphed relations.
 *
 * Unlike {@see Relation::BELONGS_TO_MORPHED} (a hard "parent before child" dependency that
 * deadlocks the pool on a cycle), the morphed refers-to relation resolves the outer key in a
 * deferred way and therefore is able to persist a closed cycle within a single transaction.
 * The related entity is resolved lazily through a promise reference.
 */
abstract class RefersToMorphedCyclicTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testFetchSelfReference(): void
    {
        // entity_a #1 references itself
        $a = $this->orm->getRepository(EntityA::class)->findByPK(1);
        $data = $this->extractEntity($a);

        \Testo\Assert::instanceOf($data['parent'], ReferenceInterface::class);

        \Testo\Assert::instanceOf($a->parent, EntityA::class);
        \Testo\Assert::same($a->parent->name, 'a-self');
        \Testo\Assert::same($a->parent->id, $a->id);
    }

    #[\Testo\Test]
    public function testFetchCycleTwoEntities(): void
    {
        // entity_a #2 -> entity_b #1 -> entity_a #2
        $a = $this->orm->getRepository(EntityA::class)->findByPK(2);

        \Testo\Assert::instanceOf($a->parent, EntityB::class);
        \Testo\Assert::same($a->parent->name, 'b-1');

        \Testo\Assert::instanceOf($a->parent->parent, EntityA::class);
        \Testo\Assert::same($a->parent->parent->id, $a->id);
    }

    /**
     * The relation is loaded lazily: a self-reference resolves to the already-loaded entity
     * straight from the heap, without issuing an extra query.
     */
    #[\Testo\Test]
    public function testLazyLoadSelfReferenceResolvesFromHeap(): void
    {
        $a = $this->orm->getRepository(EntityA::class)->findByPK(1);
        // Not resolved yet — the relation is a promise reference.
        \Testo\Assert::instanceOf($this->extractEntity($a)['parent'], ReferenceInterface::class);

        $this->captureReadQueries();
        \Testo\Assert::same($a->parent, $a);
        $this->assertNumReads(0);
    }

    /**
     * Lazy loading across a morphed cycle: resolving the parent that is not in the heap costs
     * exactly one query, while the back-reference is taken from the heap for free.
     */
    #[\Testo\Test]
    public function testLazyLoadCycleTwoEntities(): void
    {
        $a = $this->orm->getRepository(EntityA::class)->findByPK(2);
        \Testo\Assert::instanceOf($this->extractEntity($a)['parent'], ReferenceInterface::class);

        // entity_b #1 is not in the heap yet -> exactly one lazy query.
        $this->captureReadQueries();
        $b = $a->parent;
        \Testo\Assert::instanceOf($b, EntityB::class);
        $this->assertNumReads(1);

        // The back-reference entity_a #2 is already in the heap -> no extra query.
        $this->captureReadQueries();
        \Testo\Assert::same($b->parent, $a);
        $this->assertNumReads(0);
    }

    /**
     * Eager loading via Select::load(): one level of the morphed relation is resolved up front
     * (one query per distinct morph role), so accessing the parent afterwards costs no queries.
     */
    #[\Testo\Test]
    public function testEagerLoadParent(): void
    {
        /** @var list<EntityA> $all */
        $all = (new Select($this->orm, EntityA::class))
            ->load('parent')
            ->orderBy('entity_a.id')
            ->fetchAll();

        $this->captureReadQueries();
        // #1 references itself, #2 references entity_b #1 — both already loaded.
        \Testo\Assert::instanceOf($all[0]->parent, EntityA::class);
        \Testo\Assert::same($all[0]->parent, $all[0]);
        \Testo\Assert::instanceOf($all[1]->parent, EntityB::class);
        \Testo\Assert::same($all[1]->parent->name, 'b-1');
        $this->assertNumReads(0);
    }

    /**
     * The morphed relation can be eagerly loaded for a batch of already-fetched entities through
     * the BulkLoader. After that, accessing the parent issues no extra queries.
     */
    #[\Testo\Test]
    public function testBulkLoadParent(): void
    {
        $this->captureReadQueries();
        /** @var list<EntityA> $all */
        $all = (new Select($this->orm, EntityA::class))->orderBy('entity_a.id')->fetchAll();
        $this->assertNumReads(1);

        $this->bulkLoader(...$all)->load('parent')->run();

        $this->captureReadQueries();
        \Testo\Assert::instanceOf($all[0]->parent, EntityA::class);
        \Testo\Assert::same($all[0]->parent, $all[0]);
        \Testo\Assert::instanceOf($all[1]->parent, EntityB::class);
        \Testo\Assert::same($all[1]->parent->name, 'b-1');
        $this->assertNumReads(0);
    }

    #[\Testo\Test]
    public function testSetSelfReferenceToNull(): void
    {
        $a = $this->orm->getRepository(EntityA::class)->findByPK(1);
        \Testo\Assert::instanceOf($a->parent, EntityA::class);

        $a->parent = null;
        $this->save($a);

        $row = $this->getDatabase()->table('entity_a')->select()->where('id', 1)->fetchAll();
        \Testo\Assert::null($row[0]['parent_id'], 'parent_id should be NULL after detaching self-reference');
        \Testo\Assert::null($row[0]['parent_type'], 'parent_type should be NULL after detaching self-reference');
    }

    #[\Testo\Test]
    public function testCreateSelfReference(): void
    {
        $a = new EntityA();
        $a->name = 'new-self';
        $a->parent = $a;

        $this->captureWriteQueries();
        $this->save($a);
        // INSERT the row, then a deferred UPDATE with its own id + morph type
        $this->assertNumWrites(2);

        // consecutive save does nothing
        $this->captureWriteQueries();
        $this->save($a);
        $this->assertNumWrites(0);

        \Testo\Assert::same($a->parentId, $a->id);
        \Testo\Assert::same($a->parentType, 'entity_a');

        $row = $this->getDatabase()->table('entity_a')->select()->where('id', $a->id)->fetchAll();
        \Testo\Assert::same((string) $row[0]['parent_id'], (string) $a->id);
        \Testo\Assert::same($row[0]['parent_type'], 'entity_a');

        $this->orm = $this->orm->withHeap(new Heap());
        $reloaded = $this->orm->getRepository(EntityA::class)->findByPK($a->id);
        \Testo\Assert::instanceOf($reloaded->parent, EntityA::class);
        \Testo\Assert::same($reloaded->parent->id, $reloaded->id);
    }

    #[\Testo\Test]
    public function testCreateCycleTwoEntities(): void
    {
        $a = new EntityA();
        $a->name = 'cycle-a';

        $b = new EntityB();
        $b->name = 'cycle-b';

        $a->parent = $b;
        $b->parent = $a;

        $this->captureWriteQueries();
        $this->save($a);
        // INSERT a, INSERT b, then a deferred UPDATE to close the cycle
        $this->assertNumWrites(3);

        // consecutive save does nothing
        $this->captureWriteQueries();
        $this->save($a);
        $this->assertNumWrites(0);

        \Testo\Assert::same($a->parentId, $b->id);
        \Testo\Assert::same($a->parentType, 'entity_b');
        \Testo\Assert::same($b->parentId, $a->id);
        \Testo\Assert::same($b->parentType, 'entity_a');

        $this->orm = $this->orm->withHeap(new Heap());

        $reloadedA = $this->orm->getRepository(EntityA::class)->findByPK($a->id);
        \Testo\Assert::instanceOf($reloadedA->parent, EntityB::class);
        \Testo\Assert::same($reloadedA->parent->id, $b->id);
        \Testo\Assert::instanceOf($reloadedA->parent->parent, EntityA::class);
        \Testo\Assert::same($reloadedA->parent->parent->id, $a->id);
    }

    /**
     * With ignoreUninitializedRelations = true (BaseTest default) unsetting the relation property
     * must leave both the outer key and the morph key untouched.
     */
    #[\Testo\Test]
    public function testUnsetParentKeepsMorphWhenIgnoringUninitialized(): void
    {
        $a = $this->orm->getRepository(EntityA::class)->findByPK(1);
        \Testo\Assert::instanceOf($a->parent, EntityA::class);
        unset($a->parent);

        $this->captureWriteQueries();
        $this->save($a);
        $this->assertNumWrites(0);

        $row = $this->getDatabase()->table('entity_a')->select()->where('id', 1)->fetchAll();
        \Testo\Assert::same((string) $row[0]['parent_id'], '1');
        \Testo\Assert::same($row[0]['parent_type'], 'entity_a');
    }

    /**
     * With ignoreUninitializedRelations = false an unset relation is treated as null, so both the
     * outer key and the morph key must be cleared.
     */
    #[\Testo\Test]
    public function testUnsetParentClearsMorphWithoutIgnoreUninitialized(): void
    {
        $this->orm = $this->orm->with(options: (new Options())->withIgnoreUninitializedRelations(false));

        $a = $this->orm->getRepository(EntityA::class)->findByPK(1);
        \Testo\Assert::instanceOf($a->parent, EntityA::class);
        unset($a->parent);

        $this->captureWriteQueries();
        $this->save($a);
        $this->assertNumWrites(1);

        $row = $this->getDatabase()->table('entity_a')->select()->where('id', 1)->fetchAll();
        \Testo\Assert::null($row[0]['parent_id'], 'parent_id should be NULL when the unset relation is treated as null');
        \Testo\Assert::null($row[0]['parent_type'], 'parent_type should be NULL when the unset relation is treated as null');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('entity_a', [
            'id' => 'primary',
            'name' => 'string',
            'parent_id' => 'integer,nullable',
            'parent_type' => 'string,nullable',
        ]);

        $this->makeTable('entity_b', [
            'id' => 'primary',
            'name' => 'string',
            'parent_id' => 'integer,nullable',
            'parent_type' => 'string,nullable',
        ]);

        // entity_a #1 references itself; entity_a #2 -> entity_b #1
        $this->getDatabase()->table('entity_a')->insertMultiple(
            ['name', 'parent_id', 'parent_type'],
            [
                ['a-self', 1, 'entity_a'],
                ['a-cycle', 1, 'entity_b'],
            ],
        );

        // entity_b #1 -> entity_a #2 (closes the A > B > A cycle)
        $this->getDatabase()->table('entity_b')->insertMultiple(
            ['name', 'parent_id', 'parent_type'],
            [
                ['b-1', 2, 'entity_a'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            EntityA::class => [
                Schema::ROLE => 'entity_a',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'entity_a',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => [
                    'id' => 'id',
                    'name' => 'name',
                    'parentId' => 'parent_id',
                    'parentType' => 'parent_type',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'parent' => $this->morphedParent(),
                ],
            ],
            EntityB::class => [
                Schema::ROLE => 'entity_b',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'entity_b',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => [
                    'id' => 'id',
                    'name' => 'name',
                    'parentId' => 'parent_id',
                    'parentType' => 'parent_type',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'parent' => $this->morphedParent(),
                ],
            ],
        ]));
    }

    private function morphedParent(): array
    {
        return [
            Relation::TYPE => Relation::REFERS_TO_MORPHED,
            Relation::TARGET => MorphedInterface::class,
            Relation::LOAD => Relation::LOAD_PROMISE,
            Relation::SCHEMA => [
                Relation::NULLABLE => true,
                Relation::CASCADE => true,
                Relation::OUTER_KEY => 'id',
                Relation::INNER_KEY => 'parentId',
                Relation::MORPH_KEY => 'parentType',
            ],
        ];
    }
}
