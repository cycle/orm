<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Doctrine\Common\Collections\Collection;
use Spiral\ORM\Collection\PivotedCollection;
use Spiral\ORM\Collection\PivotedCollectionInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Control\ContextualSequence;
use Spiral\ORM\Command\Control\Defer;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\Command\Database\DeleteCommand;
use Spiral\ORM\Command\Database\InsertCommand;
use Spiral\ORM\Exception\RelationException;
use Spiral\ORM\Iterator;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\State;
use Spiral\ORM\Util\ContextStorage;

class ManyToManyRelation extends AbstractRelation
{
    /** @var string|null */
    private $pivotEntity;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param string       $relation
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        parent::__construct($orm, $class, $relation, $schema);
        $this->pivotEntity = $this->define(Relation::PIVOT_ENTITY);
    }

    /**
     * @inheritdoc
     */
    public function init($data): array
    {
        $elements = [];
        $pivotData = new \SplObjectStorage();

        foreach (new Iterator($this->orm, $this->class, $data) as $pivot => $entity) {
            $elements[] = $entity;
            $pivotData[$entity] = $this->initPivot($pivot);
        }

        return [new PivotedCollection($elements, $pivotData), new ContextStorage($elements, $pivotData)];
    }

    /**
     * Init pivot object if any.
     *
     * @param array $data
     * @return array
     */
    protected function initPivot(array $data)
    {
        if (empty($this->pivotEntity)) {
            return $data;
        }

        return $this->orm->make($this->pivotEntity, $data, State::LOADED);
    }

    /**
     * @inheritdoc
     */
    public function extract($data)
    {
        if ($data instanceof PivotedCollectionInterface) {
            return new ContextStorage($data->toArray(), $data->getPivotData());
        }

        if ($data instanceof Collection) {
            return new ContextStorage($data->toArray());
        }

        return new ContextStorage();
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $command,
        $entity,
        State $state,
        $related,
        $original
    ): CommandInterface {
        /**
         * @var ContextStorage $related
         * @var ContextStorage $original
         */
        $original = $original ?? new ContextStorage();

        $sequence = new Sequence();

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            $sequence->addCommand(
                $this->link($state, $item, $related->get($item), $original->get($item))
            );
        }

        // un-link old elements
        foreach ($original->getElements() as $item) {
            if (!$related->has($item)) {
                $sequence->addCommand($this->unlink($state, $item, $original->get($item)));
            }
        }

        return $sequence;
    }

    // todo: diff
    protected function link(State $parentState, $related, $context, $origContext): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);
        $relState = $this->getState($related);

        $chain = new ContextualSequence();
        $chain->addPrimary($relStore);

        if (is_object($context)) {
            // todo: check if context instance of pivot entity
            $cmd = $this->orm->queueStore($context);
            $ctxState = $this->getState($context);
        } else {
            // todo: update existed?
            // todo: store database name (!!) in relation
            $cmd = new InsertCommand(
                $this->orm->getDatabase($this->class),
                $this->define(Relation::PIVOT_TABLE),
                $context ?? []
            );
        }

        $insert = new Defer(
            $cmd,
            [
                $this->define(Relation::THOUGHT_INNER_KEY),
                $this->define(Relation::THOUGHT_OUTER_KEY)
            ],
            (string)$this
        );

        // TODO: DRY!!!
        // todo: ENTITY IS DIFFERENT!!!

        // todo: DO NOT UPDATE CONTEXT

        $this->promiseContext(
            $insert,
            $parentState,
            $this->define(Relation::INNER_KEY),
            null,
            $this->define(Relation::THOUGHT_INNER_KEY)
        );

        $this->promiseContext(
            $insert,
            $relState,
            $this->define(Relation::OUTER_KEY),
            null,
            $this->define(Relation::THOUGHT_OUTER_KEY)
        );

        $chain->addCommand($insert);

        // todo: update relation state
        return $chain;
    }

    /**
     * Remove the connection between two objects.
     *
     * @param State  $parentState
     * @param object $related
     * @return CommandInterface
     */
    protected function unlink(State $parentState, $related, $context): CommandInterface
    {
        $relState = $this->getState($related);
        if (empty($relState) || $relState->getState() == State::NEW) {
            throw new RelationException(
                "Invalid relation state, NEW entity scheduled for unlink"
            );
        }

        $delete = new DeleteCommand(
            $this->orm->getDatabase($this->class),
            $this->define(Relation::PIVOT_TABLE),
            [
                $this->define(Relation::THOUGHT_INNER_KEY) => null,
                $this->define(Relation::THOUGHT_OUTER_KEY) => null,
            ]
        );

        $this->promiseWhere(
            $delete,
            $parentState,
            $this->define(Relation::INNER_KEY),
            null,
            $this->define(Relation::THOUGHT_INNER_KEY)
        );

        $this->promiseWhere(
            $delete,
            $relState,
            $this->define(Relation::OUTER_KEY),
            null,
            $this->define(Relation::THOUGHT_OUTER_KEY)
        );

        return $delete;
    }
}