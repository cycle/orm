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
use Spiral\ORM\Command\Control\Defer;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\Command\Database\DeleteCommand;
use Spiral\ORM\Command\Database\InsertCommand;
use Spiral\ORM\Iterator;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\State;
use Spiral\ORM\Util\ContextStorage;

class ManyToManyRelation extends AbstractRelation
{
    /** @var string|null */
    private $pivotEntity;

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
        $this->pivotEntity = $this->define(Relation::PIVOT_ENTITY);
        $this->thoughtInnerKey = $this->define(Relation::THOUGHT_INNER_KEY);
        $this->thoughtOuterKey = $this->define(Relation::THOUGHT_OUTER_KEY);
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

    /**
     * Link two entities together and create/update pivot context.
     *
     * @param State  $state
     * @param object $related
     * @param mixed  $pivot
     * @param mixed  $origPivot
     * @return CommandInterface
     */
    protected function link(State $state, $related, $pivot, $origPivot): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);
        $relState = $this->getState($related);


        // deal with this clusterfuck

        if (is_object($pivot)) {
            // todo: check if context instance of pivot entity
            $upsert = $this->orm->queueStore($pivot);
            $ctxState = $this->getState($pivot);
        } else {
            // todo: WHERE IS ???
            // todo: MAKE IT SIMPLER?

            // todo: update existed?
            // todo: store database name (!!) in relation

            // it will bypass object creation!!!!

            $upsert = new InsertCommand(
                $this->orm->getDatabase($this->class),
                $this->define(Relation::PIVOT_TABLE),
                $pivot ?? []
            );

            // todo: can be existed
        }


        // TODO: DRY!!!
        // todo: ENTITY IS DIFFERENT!!!
        // todo: DO NOT UPDATE CONTEXT

        $sync = new Defer($upsert, [$this->thoughtInnerKey, $this->thoughtOuterKey], (string)$this);

        // it will always throw an insert, BUG!!!
        $this->promiseContext($sync, $state, $this->innerKey, null, $this->thoughtInnerKey);
        $this->promiseContext($sync, $relState, $this->outerKey, null, $this->thoughtOuterKey);

        $sequence = new Sequence();
        $sequence->addCommand($relStore);
        $sequence->addCommand($sync);

        return $sequence;
    }

    /**
     * Remove the connection between two objects.
     *
     * @param State  $state
     * @param object $related
     * @return CommandInterface
     */
    protected function unlink(State $state, $related, $oriPivot): CommandInterface
    {
        // delete pivot object?

        // todo: need database and table selection DRY
        $delete = new DeleteCommand(
            $this->orm->getDatabase($this->class),
            $this->define(Relation::PIVOT_TABLE)
        );

        $relState = $this->getState($related);

        $this->promiseWhere($delete, $state, $this->innerKey, null, $this->thoughtInnerKey);
        $this->promiseWhere($delete, $relState, $this->outerKey, null, $this->thoughtOuterKey);

        return $delete;
    }
}