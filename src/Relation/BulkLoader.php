<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\SchemaInterface;
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

    public function __construct(
        private ORMInterface $orm,
    ) {}

    public function collect(object ...$entities): RelationLoaderInterface
    {
        if ($entities === []) {
            return new class implements RelationLoaderInterface {
                public function load(string $relation, array $options = []): static
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

    public function load(string $relation, array $options = []): static
    {
        $this->loader->loadRelation($relation, $options, load: true);

        $role = $this->loader->getTarget();
        $relMap = $this->orm->getRelationMap($role);
        $r = $relMap->getRelations()[$relation];
        $this->keys = \array_merge($this->keys, $r->getInnerKeys());

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

        foreach ($this->entities as $entity) {
            $n = $heap->get($entity) ?? throw new \LogicException("Entity node not found in the heap.");
            // Use Node data to load relations instead of actual entity data
            // to avoid inconsistent state in the Heap
            $data = self::normalizeKeys($mapper->uncast($n->getData()), $keys);
            $this->indexEntity($n, $pk, $data, $entity);
            $node->push($data);
            unset($data);
        }

        $this->loader->loadData($node, true);
        $result = $node->getResult();

        // Fill entities with loaded relations
        foreach ($result as $data) {
            [$entity, $node] = $this->getEntity($pk, $data);
            $fetched = $mapper->fetchRelations($entity);

            // Get not resolved (references) or not set relations
            $overwrite = [];
            foreach ($relations as $name => $_) {
                if (\array_key_exists($name, $data) && ($fetched[$name] ?? null) instanceof ReferenceInterface) {
                    $overwrite[$name] = $data[$name];
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
     * Only keys matter for relations loading, so unnecessary data will be filtered out.
     *
     * @param non-empty-array<non-empty-string> $keys
     * @return non-empty-array<non-empty-string, mixed>
     */
    private static function normalizeKeys(array $data, array $keys): array
    {
        $result = [];
        foreach ($keys as $k) {
            \array_key_exists($k, $data) or throw new \LogicException(
                "Bulk loader cannot get the value for the key `$k`.",
            );
            $result[$k] = Node::convertToSolid($data[$k]);
        }
        return $result;
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
