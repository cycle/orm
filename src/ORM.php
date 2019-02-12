<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

use Spiral\Cycle\Command\Branch\Nil;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Exception\ORMException;
use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Heap\HeapInterface;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\MapperInterface;
use Spiral\Cycle\Promise\PromiseInterface;
use Spiral\Cycle\Select\SourceFactoryInterface;
use Spiral\Cycle\Select\SourceInterface;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 */
class ORM implements ORMInterface, SourceFactoryInterface
{
    /** @var CommandGenerator */
    private $generator;

    /** @var FactoryInterface|SourceFactoryInterface */
    private $factory;

    /** @var HeapInterface */
    private $heap;

    /** @var SchemaInterface */
    private $schema;

    /** @var MapperInterface[] */
    private $mappers = [];

    /** @var RelationMap[] */
    private $relmaps = [];

    /** @var array */
    private $indexes = [];

    /** @var SourceInterface[] */
    private $sources = [];

    /**
     * @param FactoryInterface|SourceFactoryInterface $factory
     * @param SchemaInterface|null                    $schema
     */
    public function __construct(FactoryInterface $factory, SchemaInterface $schema = null)
    {
        if (!$factory instanceof SourceFactoryInterface) {
            throw new ORMException("Source factory is missing");
        }

        $this->generator = new CommandGenerator();
        $this->factory = $factory;

        if (!is_null($schema)) {
            $this->schema = $schema;
            $this->factory = $this->factory->withSchema($this, $schema);
        }

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

        return $this->schema->resolveRole($entity);
    }

    /**
     * @inheritdoc
     */
    public function get(string $role, $id, bool $load = true)
    {
        $role = $this->resolveRole($role);
        $pk = $this->schema->define($role, Schema::PRIMARY_KEY);

        if (!is_null($e = $this->heap->find($role, $pk, $id))) {
            return $e;
        }

        if (!$load) {
            return null;
        }

        return $this->getMapper($role)->getRepository()->findByPK($id);
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
            if (!empty($e = $this->heap->find($role, $pk, $id))) {
                $node = $this->getHeap()->get($e);

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

        if (!is_null($orm->schema)) {
            $orm->factory = $factory->withSchema($orm, $orm->schema);
        }

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
        $orm->factory = $orm->factory->withSchema($orm, $orm->schema);

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
        $orm->factory = $orm->factory->withSchema($orm, $orm->schema);

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

        return $this->mappers[$role] = $this->factory->mapper($role);
    }

    /**
     * @inheritdoc
     */
    public function getSource(string $role): SourceInterface
    {
        if (isset($this->sources[$role])) {
            return $this->sources[$role];
        }

        return $this->sources[$role] = $this->factory->getSource($role);
    }

    /**
     * @inheritdoc
     */
    public function queueStore($entity, int $mode = TransactionInterface::MODE_CASCADE): ContextCarrierInterface
    {
        if ($entity instanceof PromiseInterface) {
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
        if ($entity instanceof PromiseInterface || is_null($node)) {
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
            $relations[$relation] = $this->factory->relation($role, $relation);
        }

        return $this->relmaps[$role] = new RelationMap($this, $relations);
    }
}