<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\ManyToMany;

use Spiral\ORM\Exception\RelationException;
use Spiral\ORM\Iterator;
use Spiral\ORM\Loader\JoinableLoader;
use Spiral\ORM\Loader\Relation\ManyToManyLoader;
use Spiral\ORM\Mapper\SelectableInterface;
use Spiral\ORM\Node;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\TreeGenerator\PivotedRootNode;
use Spiral\ORM\Util\ContextStorage;
use Spiral\ORM\Util\Promise\PivotedPromiseInterface;

/**
 * Promise use loader to configure query and it's scope.
 */
class PivotedPromise implements PivotedPromiseInterface
{
    /** @var ORMInterface */
    private $orm;

    /** @var string */
    private $target;

    /** @var array */
    private $relationSchema = [];

    /** @var mixed */
    private $innerKey;

    /** @var null|ContextStorage */
    private $resolved;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $relationSchema
     * @param mixed        $innerKey
     */
    public function __construct(ORMInterface $orm, string $target, array $relationSchema, $innerKey)
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->relationSchema = $relationSchema;
        $this->innerKey = $innerKey;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return empty($this->orm);
    }

    /**
     * @inheritdoc
     */
    public function __role(): string
    {
        return $this->target;
    }

    /**
     * @inheritdoc
     */
    public function __scope(): array
    {
        return $this->innerKey;
    }

    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        return $this->__doResolve()->getElements();
    }

    /**
     * Return promised pivot context.
     *
     * @return ContextStorage
     */
    public function __resolveContext(): ContextStorage
    {
        return $this->__doResolve();
    }

    /**
     * @return ContextStorage
     */
    protected function __doResolve(): ContextStorage
    {
        if (is_null($this->orm)) {
            return $this->resolved;
        }

        $mapper = $this->orm->getMapper($this->target);
        if (!$mapper instanceof SelectableInterface) {
            throw new RelationException("ManyToMany relation can only work with SelectableInterface mappers");
        }

        $query = $mapper->getSelector()->getLoader()->getQuery();

        // responsible for all the scoping
        $loader = new ManyToManyLoader(
            $this->orm,
            $this->target,
            $mapper->getTable(),
            $this->relationSchema
        );

        /** @var ManyToManyLoader $loader */
        $loader = $loader->withContext($loader, [
            'alias'      => $mapper->getTable(),
            'pivotAlias' => $mapper->getTable() . '_pivot',
            'method'     => JoinableLoader::POSTLOAD
        ]);

        $query = $loader->configureQuery($query, [$this->innerKey]);

        $node = new PivotedRootNode(
            $this->orm->getSchema()->define($this->target, Schema::COLUMNS),
            $this->relationSchema[Relation::PIVOT_COLUMNS],
            $this->relationSchema[Relation::OUTER_KEY],
            $this->relationSchema[Relation::THOUGHT_INNER_KEY],
            $this->relationSchema[Relation::THOUGHT_OUTER_KEY]
        );

        $iterator = $query->getIterator();
        foreach ($iterator as $row) {
            $node->parseRow(0, $row);
        }
        $iterator->close();


        $elements = [];
        $pivotData = new \SplObjectStorage();
        foreach (new Iterator($this->orm, $this->target, $node->getResult()) as $pivot => $entity) {
            if (!empty($this->relationSchema[Relation::PIVOT_ENTITY])) {
                $pivotData[$entity] = $this->orm->make(
                    $this->relationSchema[Relation::PIVOT_ENTITY],
                    $pivot,
                    Node::MANAGED
                );
            } else {
                $pivotData[$entity] = $pivot;
            }

            $elements[] = $entity;
        }

        $this->resolved = new ContextStorage($elements, $pivotData);
        $this->orm = null;

        return $this->resolved;
    }
}