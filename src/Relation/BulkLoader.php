<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\UpdateLoader;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Service\SourceProviderInterface;

/**
 * @internal
 */
final class BulkLoader implements BulkLoaderInterface, RelationLoaderInterface
{
    /** @var list<object> */
    private array $entities = [];

    private UpdateLoader $loader;
    private array $index = [];

    /** @var list<non-empty-string> Keys matter for relations */
    private array $keys = [];

    /** @var array<non-empty-string, SameRowRelationInterface> Embedded (same-row) relations to load */
    private array $embedded = [];

    public function __construct(
        private ORMInterface $orm,
    ) {}

    public function collect(object ...$entities): RelationLoaderInterface
    {
        if ($entities === []) {
            return new class implements RelationLoaderInterface {
                public function load(string $relation, LoadOptions|array $options = []): static
                {
                    return $this;
                }

                public function run(): void {}
            };
        }
        $entities = \array_values($entities);

        // Validate entity roles
        $role = $this->orm->resolveRole($entities[0]);
        foreach ($entities as $entity) {
            // todo STI/JTI support
            if ($this->orm->resolveRole($entity) !== $role) {
                throw new \InvalidArgumentException('All entities must belong to the same role.');
            }
        }

        $clone = clone $this;
        $clone->entities = \array_merge($this->entities, $entities);
        $clone->loader = new UpdateLoader(
            $this->orm->getSchema(),
            $this->orm->getService(SourceProviderInterface::class),
            $this->orm->getFactory(),
            $role,
        );

        return $clone;
    }

    public function load(string $relation, LoadOptions|array $options = []): static
    {
        $this->loader->loadRelation($relation, $options, load: true);

        // Determine inner keys for the relation
        $role = $this->loader->getTarget();
        $relMap = $this->orm->getRelationMap($role);
        $parentRel = \explode('.', $relation, 2)[0];
        \assert($parentRel !== '');
        $r = $relMap->getRelations()[$parentRel];
        $this->keys = \array_merge($this->keys, $r->getInnerKeys());

        // Same-row (embedded) relations aren't fed by the joined loader tree because
        // UpdateLoader doesn't issue a parent SELECT. Track them for a batched fetch in run().
        if ($r instanceof SameRowRelationInterface) {
            $this->embedded[$parentRel] = $r;
        }

        return $this;
    }

    public function run(): void
    {
        $role = $this->loader->getTarget();
        $mapper = $this->orm->getMapper($role);
        $node = $this->loader->createNode();
        $pk = (array) $this->orm->getSchema()->define($role, SchemaInterface::PRIMARY_KEY);
        $relMap = $this->orm->getRelationMap($role);
        $relations = $relMap->getRelations();
        $heap = $this->orm->getHeap();
        $factory = $this->orm->getService(EntityFactoryInterface::class);
        $keys = \array_unique(\array_merge($this->keys, $pk));

        $ids = [];
        foreach ($this->entities as $entity) {
            $n = $heap->get($entity) ?? throw new \LogicException("Entity node not found in the heap.");
            // Use Node data to load relations instead of actual entity data
            // to avoid inconsistent state in the Heap
            $data = $mapper->uncast($n->getData());
            self::normalizeKeys($data, $keys);
            $this->indexEntity($n, $pk, $data, $entity);
            $node->push($data);

            if ($this->embedded !== []) {
                $ids[] = \count($pk) === 1
                    ? $data[$pk[0]]
                    : \array_intersect_key($data, \array_flip($pk));
            }
            unset($data);
        }

        $this->loader->loadData($node, true);
        $result = $node->getResult();

        // Embedded (same-row) relations: the joined sub-loader can't fetch data on its own,
        // so warm the heap with the embedded entities in a single batch query.
        if ($ids !== []) {
            $this->loadEmbedded($ids);
        }

        // Fill entities with loaded relations
        foreach ($result as $data) {
            [$entity, $node] = $this->getEntity($pk, $data);
            $fetched = $mapper->fetchRelations($entity);

            // Get not resolved (references) or not set relations
            $overwrite = [];
            foreach ($relations as $name => $_) {
                // Embedded placeholders ($data[$name] === null) are filled in the next loop.
                if (isset($this->embedded[$name])) {
                    continue;
                }
                if (\array_key_exists($name, $data) && ($fetched[$name] ?? null) instanceof ReferenceInterface) {
                    $overwrite[$name] = $data[$name];
                }
            }

            // Resolve each tracked embedded reference; values come from the heap warmed above.
            foreach ($this->embedded as $name => $relation) {
                $ref = $fetched[$name] ?? null;
                if (!$ref instanceof ReferenceInterface) {
                    continue;
                }
                $value = $relation->resolve($ref, true);
                if ($value !== null) {
                    $overwrite[$name] = $value;
                }
            }

            if ($overwrite === []) {
                continue;
            }

            $mapper->hydrate($entity, $relMap->init($factory, $node, $mapper->cast($overwrite)));
        }

        $this->index = [];
    }

    /**
     * Normalize data by provided keys.
     *
     * @param non-empty-array<non-empty-string> $keys
     */
    private static function normalizeKeys(array &$data, array $keys): void
    {
        foreach ($keys as $k) {
            \array_key_exists($k, $data) or throw new \LogicException(
                "Bulk loader cannot get the value for the key `$k`.",
            );
            $data[$k] = Node::convertToSolid($data[$k]);
        }
    }

    /**
     * Warm the heap with embedded entities in a single batch query.
     *
     * All embedded roles share the parent's table, so one Select on the parent role with every
     * embedded relation chained as ->load() collapses N embedded fetches into one query. Parent
     * fields on existing heap entities are preserved — RelationMap::init skips relations that are
     * already set on the node, and Mapper::hydrate isn't invoked on the parent here.
     *
     * @param non-empty-list<scalar|array<non-empty-string, scalar>> $ids
     */
    private function loadEmbedded(array $ids): void
    {
        $select = new Select($this->orm, $this->loader->getTarget());
        foreach ($this->embedded as $name => $_) {
            $select->load($name);
        }
        $select->wherePK(...$ids)->fetchAll();
    }

    /**
     * Index entity by provided keys and data.
     *
     * @param non-empty-array<non-empty-string> $keys
     * @param non-empty-array<non-empty-string> $data
     */
    private function indexEntity(Node $node, array $keys, array $data, object $entity): void
    {
        $pool = &$this->index;
        foreach ($keys as $k) {
            $keyValue = $data[$k];
            \is_scalar($keyValue) or throw new \InvalidArgumentException(
                "Invalid value on the primary key `$k`. Expected scalar, got " . \get_debug_type($keyValue) . ".",
            );
            $pk[$k] = $keyValue;

            \array_key_exists($keyValue, $pool) or $pool[$keyValue] = [];
            $pool = &$pool[$keyValue];
        }


        $pool = [$entity, $node];
    }

    /**
     * Fetch indexed entity by provided keys and data.
     *
     * @param non-empty-array<non-empty-string> $pk
     * @param non-empty-array<non-empty-string> $data
     *
     * @return array{object, Node} The indexed entity and its node
     */
    private function getEntity(array $pk, array $data): array
    {
        $result = $this->index;
        foreach ($pk as $k) {
            $result = $result[$data[$k]] ?? throw new \LogicException('Cannot find indexed entity.');
        }

        return $result;
    }
}
