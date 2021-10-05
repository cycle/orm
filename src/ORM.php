<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Transaction\CommandGenerator;
use Cycle\ORM\Transaction\CommandGeneratorInterface;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 *
 * @template-extends ORMInterface
 */
final class ORM implements ORMInterface
{
    private HeapInterface $heap;

    /** @var MapperInterface[] */
    private array $mappers = [];

    /** @var RepositoryInterface[] */
    private array $repositories = [];

    /** @var RelationMap[] */
    private array $relMaps = [];

    private array $indexes = [];

    /** @var SourceInterface[] */
    private array $sources = [];

    private CommandGeneratorInterface $commandGenerator;

    public function __construct(
        private FactoryInterface $factory,
        private SchemaInterface $schema,
        CommandGeneratorInterface $commandGenerator = null
    ) {
        $this->heap = new Heap();
        $this->commandGenerator = $commandGenerator ?? new CommandGenerator();
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->heap = new Heap();
        $this->mappers = [];
        $this->relMaps = [];
        $this->indexes = [];
        $this->sources = [];
        $this->repositories = [];
    }

    public function __debugInfo(): array
    {
        return [
            'schema' => $this->schema,
        ];
    }

    public function resolveRole(string|object $entity): string
    {
        if (\is_object($entity)) {
            $node = $this->getHeap()->get($entity);
            if ($node !== null) {
                return $node->getRole();
            }

            $class = $entity::class;
            if (!$this->schema->defines($class)) {
                // todo: redesign
                // temporary solution for proxy objects
                $parentClass = get_parent_class($entity);
                if ($parentClass === false
                    || substr($class, -6) !== chr(160) . 'Proxy'
                    || !$this->schema->defines($parentClass)
                ) {
                    throw new ORMException("Unable to resolve role of `$class`.");
                }
                $class = $parentClass;
            }

            $entity = $class;
        }

        return $this->schema->resolveAlias($entity);
    }

    public function get(string $role, array $scope, bool $load = true): ?object
    {
        $role = $this->resolveRole($role);
        $e = $this->heap->find($role, $scope);

        if ($e !== null) {
            return $e;
        }

        if (!$load) {
            return null;
        }

        return $this->getRepository($role)->findOne($scope);
    }

    public function make(string $role, array $data = [], int $status = Node::NEW): object
    {
        $role = $data[LoaderInterface::ROLE_KEY] ?? $role;
        unset($data[LoaderInterface::ROLE_KEY]);
        $relMap = $this->getRelationMap($role);
        $mapper = $this->getMapper($role);
        if ($status !== Node::NEW) {
            // unique entity identifier
            $pk = $this->schema->define($role, SchemaInterface::PRIMARY_KEY);
            if (\is_array($pk)) {
                $ids = [];
                foreach ($pk as $key) {
                    if (!isset($data[$key])) {
                        $ids = null;
                        break;
                    }
                    $ids[$key] = $data[$key];
                }
            } else {
                $ids = isset($data[$pk]) ? [$pk => $data[$pk]] : null;
            }

            if ($ids !== null) {
                $e = $this->heap->find($role, $ids);

                if ($e !== null) {
                    $node = $this->heap->get($e);
                    \assert($node !== null);
                    $data = $relMap->init($node, $data);

                    return $mapper->hydrate($e, $data);
                }
            }
        }

        $node = new Node($status, $data, $role);
        $e = $mapper->init($data, $role);

        /** Entity should be attached before {@see RelationMap::init()} running */
        $this->heap->attach($e, $node, $this->getIndexes($role));

        $data = $relMap->init($node, $data);
        return $mapper->hydrate($e, $data);
    }

    public function getCommandGenerator(): CommandGeneratorInterface
    {
        return $this->commandGenerator;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

    public function getHeap(): HeapInterface
    {
        return $this->heap;
    }

    public function getMapper(string|object $entity): MapperInterface
    {
        $role = $this->resolveRole($entity);
        return $this->mappers[$role] ?? ($this->mappers[$role] = $this->factory->mapper($this, $role));
    }

    public function getRepository(string|object $entity): RepositoryInterface
    {
        $role = $this->resolveRole($entity);
        if (isset($this->repositories[$role])) {
            return $this->repositories[$role];
        }

        $select = null;

        if ($this->schema->define($role, SchemaInterface::TABLE) !== null) {
            $select = new Select($this, $role);
            $select->scope($this->getSource($role)->getScope());
        }

        return $this->repositories[$role] = $this->factory->repository($this, $this->schema, $role, $select);
    }

    public function getSource(string $role): SourceInterface
    {
        if (isset($this->sources[$role])) {
            return $this->sources[$role];
        }

        return $this->sources[$role] = $this->factory->source($this, $this->schema, $role);
    }

    public function promise(string $role, array $scope): object
    {
        if (\count($scope) === 1) {
            $e = $this->heap->find($role, $scope);
            if ($e !== null) {
                return $e;
            }
        }

        return new Reference($role, $scope);
    }

    public function getIndexes(string $role): array
    {
        if (isset($this->indexes[$role])) {
            return $this->indexes[$role];
        }

        $pk = $this->schema->define($role, SchemaInterface::PRIMARY_KEY);
        $keys = $this->schema->define($role, SchemaInterface::FIND_BY_KEYS) ?? [];

        return $this->indexes[$role] = array_unique(array_merge([$pk], $keys), SORT_REGULAR);
    }

    /**
     * Get relation map associated with the given class.
     *
     * todo: the ORMInterface hasn't this method
     */
    public function getRelationMap(string $entity): RelationMap
    {
        $role = $this->resolveRole($entity);
        return $this->relMaps[$role] ?? ($this->relMaps[$role] = RelationMap::build($this, $role));
    }

    public function withSchema(SchemaInterface $schema): ORMInterface
    {
        $orm = clone $this;
        $orm->schema = $schema;

        return $orm;
    }

    public function withFactory(FactoryInterface $factory): ORMInterface
    {
        $orm = clone $this;
        $orm->factory = $factory;

        return $orm;
    }

    public function withHeap(HeapInterface $heap): ORMInterface
    {
        $orm = clone $this;
        $orm->heap = $heap;

        return $orm;
    }
}
