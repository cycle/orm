<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Spiral\Database\DatabaseInterface;
use Spiral\ORM\Collection\PromisedCollection;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Control\Nil;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\Command\Database\Delete;
use Spiral\ORM\Command\Database\Insert;
use Spiral\ORM\Iterator;
use Spiral\ORM\Loader\Relation\ManyToManyLoader;
use Spiral\ORM\Loader\RelationLoader;
use Spiral\ORM\Node\PivotedRootNode;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Promise\ContextPromise;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\State;
use Spiral\ORM\StateInterface;
use Spiral\ORM\Util\ContextStorage;

class ManyToManyRelation extends AbstractRelation
{
    /** @var string */
    protected $thoughtInnerKey;

    /** @var string */
    protected $thoughtOuterKey;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param string       $relation
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        parent::__construct($orm, $class, $relation, $schema);
        $this->thoughtInnerKey = $this->define(Relation::THOUGHT_INNER_KEY);
        $this->thoughtOuterKey = $this->define(Relation::THOUGHT_OUTER_KEY);
    }

    public function initPromise(State $state, $data): array
    {
        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return [null, null];
        }

        // todo: context promise (!)
        $pr = new ContextPromise(
            [$this->outerKey => $innerKey],
            function () use ($innerKey) {
                // todo: store pivot context as well!!! or NOT?

                // todo: need easy way to get access to table
                $tableName = $this->orm->getSchema()->define($this->class, Schema::TABLE);

                // todo: i need parent entity name
                $query = $this->orm->getDatabase($this->class)->select()->from($tableName);

                $loader = new ManyToManyLoader($this->orm, $this->class, $this->relation, $this->schema);

                $loader = $loader->withContext(
                    $loader,
                    [
                        'alias'      => $tableName,
                        'pivotAlias' => $tableName . '_pivot',
                        'method'     => RelationLoader::POSTLOAD
                    ]
                );

                /** @var ManyToManyLoader $loader */
                $query = $loader->configureQuery($query, [$innerKey]);

                $node = new PivotedRootNode(
                    $this->orm->getSchema()->define($this->class, Schema::COLUMNS),
                    $this->schema[Relation::PIVOT_COLUMNS],
                    $this->schema[Relation::OUTER_KEY],
                    $this->schema[Relation::THOUGHT_INNER_KEY],
                    $this->schema[Relation::THOUGHT_OUTER_KEY]
                );

                $iterator = $query->getIterator();
                foreach ($iterator as $row) {
                    $node->parseRow(0, $row);
                }
                $iterator->close();


                $elements = [];
                $pivotData = new \SplObjectStorage();
                foreach (new Iterator($this->orm, $this->class, $node->getResult()) as $pivot => $entity) {
                    $pivotData[$entity] = $pivot;
                    $elements[] = $entity;
                }

                return new ContextStorage($elements, $pivotData);
            }
        );

        return [new PromisedCollection($pr), $pr];
    }

    /**
     * @inheritdoc
     */
    public function init($data): array
    {
        $elements = [];
        $pivotData = new \SplObjectStorage();

        foreach (new Iterator($this->orm, $this->class, $data) as $pivot => $entity) {
            $pivotData[$entity] = $pivot;
            $elements[] = $entity;
        }

        return [new ArrayCollection($elements), new ContextStorage($elements, $pivotData)];
    }

    /**
     * @inheritdoc
     */
    public function extract($data)
    {
        if ($data instanceof PromisedCollection && !$data->isInitialized()) {
            return $data->getPromise();
        }

        if ($data instanceof Collection) {
            return new ContextStorage($data->toArray());
        }

        return new ContextStorage();
    }

    /**
     * @inheritdoc
     *
     * @param ContextStorage $related
     * @param ContextStorage $original
     */
    public function queueRelation(
        ContextualInterface $parent,
        $entity,
        StateInterface $state,
        $related,
        $original
    ): CommandInterface {
        $original = $original ?? new ContextStorage();

        if ($related instanceof ContextPromise) {
            if ($related === $original) {
                return new Nil();
            }

            // todo: unify?
            $related = $related->__resolveContext();
        }

        if ($original instanceof ContextPromise) {
            // todo: check consecutive changes
            $original = $original->__resolveContext();
            // todo: state->setRelation (!!!!!!)
        }

        $sequence = new Sequence();

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            $sequence->addCommand(
                $this->link($state, $item, $original->has($item))
            );
        }

        // un-link old elements
        foreach ($original->getElements() as $item) {
            if (!$related->has($item)) {
                $sequence->addCommand(
                    $this->unlink($state, $item)
                );
            }
        }

        return $sequence;
    }

    /**
     * Link two entities together and create/update pivot context.
     *
     * @param StateInterface $state
     * @param object         $related
     * @param bool           $exists
     * @return CommandInterface
     */
    protected function link(StateInterface $state, $related, $exists): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);

        if ($exists) {
            // no changes in relation between the objects
            return $relStore;
        }

        $sync = new Insert($this->pivotDatabase(), $this->pivotTable());

        $this->promiseContext($sync, $state, $this->innerKey, null, $this->thoughtInnerKey);
        $this->promiseContext($sync, $this->getState($related), $this->outerKey, null, $this->thoughtOuterKey);

        $sequence = new Sequence();
        $sequence->addCommand($relStore);
        $sequence->addCommand($sync);

        return $sequence;
    }

    /**
     * Remove the connection between two objects.
     *
     * @param StateInterface $state
     * @param object         $related
     * @return CommandInterface
     */
    protected function unlink(StateInterface $state, $related): CommandInterface
    {
        $delete = new Delete($this->pivotDatabase(), $this->pivotTable());

        $this->promiseScope($delete, $state, $this->innerKey, null, $this->thoughtInnerKey);
        $this->promiseScope($delete, $this->getState($related), $this->outerKey, null, $this->thoughtOuterKey);

        return $delete;
    }

    /**
     * @return DatabaseInterface
     */
    protected function pivotDatabase(): DatabaseInterface
    {
        return $this->orm->getDatabase($this->class);
    }

    /**
     * @return string
     */
    protected function pivotTable(): string
    {
        return $this->define(Relation::PIVOT_TABLE);
    }
}