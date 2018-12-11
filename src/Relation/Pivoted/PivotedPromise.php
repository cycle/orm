<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Relation\Pivoted;

use Spiral\Cycle\Exception\RelationException;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Iterator;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Parser\PivotedRootNode;
use Spiral\Cycle\Promise\PromiseInterface;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector\JoinableLoader;
use Spiral\Cycle\Selector\Loader\ManyToManyLoader;
use Spiral\Cycle\Selector\SourceInterface;

/**
 * Promise use loader to configure query and it's scope.
 */
class PivotedPromise implements PromiseInterface
{
    /** @var ORMInterface */
    private $orm;

    /** @var string */
    private $target;

    /** @var array */
    private $relationSchema = [];

    /** @var mixed */
    private $innerKey;

    /** @var null|PivotedStorage */
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
     * @return PivotedStorage
     */
    public function __resolve()
    {
        if (is_null($this->orm)) {
            return $this->resolved;
        }

        $mapper = $this->orm->getMapper($this->target);
        if (!$mapper instanceof SourceInterface) {
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

        $this->resolved = new PivotedStorage($elements, $pivotData);
        $this->orm = null;

        return $this->resolved;
    }
}