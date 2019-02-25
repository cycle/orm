<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\Reference;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Select\SourceProviderInterface;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 */
class ORM implements ORMInterface
{
    /** @var CommandGenerator */
    private $generator;

    /** @var FactoryInterface|SourceProviderInterface */
    private $factory;

    /** @var PromiseFactoryInterface|null */
    private $promiseFactory;

    /** @var HeapInterface */
    private $heap;

    /** @var SchemaInterface|null */
    private $schema;

    /** @var MapperInterface[] */
    private $mappers = [];

    /** @var RepositoryInterface[] */
    private $repositories = [];

    /** @var RelationMap[] */
    private $relmaps = [];

    /** @var array */
    private $indexes = [];

    /** @var SourceInterface[] */
    private $sources = [];

    /**
     * @param FactoryInterface|SourceProviderInterface $factory
     * @param SchemaInterface|null                     $schema
     */
    public function __construct(FactoryInterface $factory, SchemaInterface $schema = null)
    {
        $this->generator = new CommandGenerator();
        $this->factory = $factory;
        $this->schema = $schema;

        $this->heap = new Heap();
    }

    /**
     * Automatically resolve role based on object name.
     *
     * @param string|object $entity
     * @return string
     */
    public function resolveRole($entity): string
    {
        if (is_object($entity)) {
            $class = get_class($entity);
            if (!$this->schema->defines($class)) {
                $node = $this->getHeap()->get($entity);
                if (is_null($node)) {
                    throw new ORMException("Unable to resolve role of `$class`");
                }

                return $node->getRole();
            }

            $entity = $class;
        }

        return $this->schema->resolveAlias($entity);
    }

    /**
     * @inheritdoc
     */
    public function get(string $role, string $key, $value, bool $load = true)
    {
        $role = $this->resolveRole($role);
        if (!is_null($e = $this->heap->find($role, $key, $value))) {
            return $e;
        }

        if (!$load) {
            return null;
        }

        return $this->getRepository($role)->findByPK($value);
    }

    /**
     * @inheritdoc
     */
    public function make(string $role, array $data = [], int $node = Node::NEW)
    {
        $m = $this->getMapper($role);

        // unique entity identifier
        $pk = $this->schema->define($role, Schema::PRIMARY_KEY);
        $id = $data[$pk] ?? null;

        if ($node !== Node::NEW && !empty($id)) {
            $e = $this->heap->find($role, $pk, $id);
            if ($e !== null) {
                $node = $this->heap->get($e);

                // entity already been loaded, let's update it's relations with new context
                return $m->hydrate($e, $this->getRelmap($role)->init($node, $data));
            }
        }

        // init entity class and prepared (typecasted) data
        list($e, $prepared) = $m->init($data);

        $node = new Node($node, $prepared, $m->getRole());

        $this->heap->attach($e, $node, $this->getIndexes($m->getRole()));

        // hydrate entity with it's data, relations and proxies
        return $m->hydrate($e, $this->getRelmap($role)->init($node, $prepared));
    }

    /**
     * @inheritdoc
     */
    public function withFactory(FactoryInterface $factory): ORMInterface
    {
        $orm = clone $this;
        $orm->factory = $factory;

        return $orm;
    }

    /**
     * @inheritdoc
     */
    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    /**
     * @inheritdoc
     */
    public function withSchema(SchemaInterface $schema): ORMInterface
    {
        $orm = clone $this;
        $orm->schema = $schema;

        return $orm;
    }

    /**
     * @inheritdoc
     */
    public function getSchema(): SchemaInterface
    {
        if (is_null($this->schema)) {
            throw new ORMException("ORM is not configured, schema is missing");
        }

        return $this->schema;
    }

    /**
     * @inheritdoc
     */
    public function withHeap(HeapInterface $heap): ORMInterface
    {
        $orm = clone $this;
        $orm->heap = $heap;

        return $orm;
    }

    /**
     * @inheritdoc
     */
    public function getHeap(): HeapInterface
    {
        return $this->heap;
    }

    /**
     * @inheritdoc
     */
    public function getMapper($entity): MapperInterface
    {
        $role = $this->resolveRole($entity);
        if (isset($this->mappers[$role])) {
            return $this->mappers[$role];
        }

        return $this->mappers[$role] = $this->factory->mapper($this, $this->schema, $role);
    }

    /**
     * @inheritdoc
     */
    public function getRepository($entity): RepositoryInterface
    {
        $role = $this->resolveRole($entity);
        if (isset($this->repositories[$role])) {
            return $this->repositories[$role];
        }

        $selector = new Select($this, $role);
        $selector->constrain($this->getSource($role)->getConstrain());

        $repositoryClass = $this->getSchema()->define($role, Schema::REPOSITORY) ?? Repository::class;

        return $this->repositories[$role] = new $repositoryClass($selector);
    }

    /**
     * @inheritdoc
     */
    public function getSource(string $role): SourceInterface
    {
        if (isset($this->sources[$role])) {
            return $this->sources[$role];
        }

        $source = $this->schema->define($role, Schema::SOURCE);
        if ($source !== null) {
            return $this->factory->get($source);
        }

        $source = new Source(
            $this->factory->database($this->schema->define($role, Schema::DATABASE)),
            $this->schema->define($role, Schema::TABLE)
        );

        $constrain = $this->schema->define($role, Schema::CONSTRAIN);
        if ($constrain !== null) {
            $source = $source->withConstrain($this->factory->get($constrain));
        }

        return $this->sources[$role] = $source;
    }

    /**
     * Overlay existing promise factory.
     *
     * @param PromiseFactoryInterface $promiseFactory
     * @return ORM
     */
    public function withPromiseFactory(PromiseFactoryInterface $promiseFactory): self
    {
        $orm = clone $this;
        $orm->promiseFactory = $promiseFactory;

        return $orm;
    }

    /**
     * @inheritdoc
     *
     * Returns references by default.
     */
    public function promise(string $role, array $scope)
    {
        $e = $this->heap->find($role, key($scope), current($scope));
        if ($e !== null) {
            return $e;
        }

        if ($this->promiseFactory !== null) {
            return $this->promiseFactory->promise($this, $role, $scope);
        }

        return new Reference($role, $scope);
    }

    /**
     * @inheritdoc
     */
    public function queueStore($entity, int $mode = TransactionInterface::MODE_CASCADE): ContextCarrierInterface
    {
        if ($entity instanceof ReferenceInterface) {
            // we do not expect to store promises
            return new Nil();
        }

        $mapper = $this->getMapper($entity);

        $node = $this->heap->get($entity);
        if (is_null($node)) {
            // automatic entity registration
            $node = new Node(Node::NEW, [], $mapper->getRole());
            $this->heap->attach($entity, $node);
        }

        $cmd = $this->generator->generateStore($mapper, $entity, $node);
        if ($mode != TransactionInterface::MODE_CASCADE) {
            return $cmd;
        }

        // generate set of commands required to store entity relations
        return $this->getRelmap($node->getRole())->queueRelations(
            $cmd,
            $entity,
            $node,
            $mapper->extract($entity)
        );
    }

    /**
     * @inheritdoc
     */
    public function queueDelete($entity, int $mode = TransactionInterface::MODE_CASCADE): CommandInterface
    {
        $node = $this->heap->get($entity);
        if ($entity instanceof ReferenceInterface || is_null($node)) {
            // nothing to do, what about promises?
            return new Nil();
        }

        // currently we rely on db to delete all nested records (or soft deletes)
        return $this->generator->generateDelete($this->getMapper($node->getRole()), $entity, $node);
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->mappers = [];
        $this->relmaps = [];
        $this->indexes = [];
        $this->sources = [];
        $this->repositories = [];
    }

    /**
     * Get list of keys entity must be indexed in a Heap by.
     *
     * @param string $role
     * @return array
     */
    protected function getIndexes(string $role): array
    {
        if (isset($this->indexes[$role])) {
            return $this->indexes[$role];
        }

        $pk = $this->schema->define($role, Schema::PRIMARY_KEY);
        $keys = $this->schema->define($role, Schema::FIND_BY_KEYS) ?? [];

        return $this->indexes[$role] = array_merge([$pk], $keys);
    }

    /**
     * Get relation map associated with the given class.
     *
     * @param string $entity
     * @return RelationMap
     */
    protected function getRelmap($entity): RelationMap
    {
        $role = $this->resolveRole($entity);
        if (isset($this->relmaps[$role])) {
            return $this->relmaps[$role];
        }

        $relations = [];

        $names = array_keys($this->schema->define($role, Schema::RELATIONS));
        foreach ($names as $relation) {
            $relations[$relation] = $this->factory->relation($this, $this->schema, $role, $relation);
        }

        return $this->relmaps[$role] = new RelationMap($this, $relations);
    }
}
