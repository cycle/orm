<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\ManyToMany;

use Doctrine\Common\Collections\Collection;
use Spiral\ORM\Collection\PivotedCollection;
use Spiral\ORM\Collection\PivotedCollectionInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\Iterator;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\State;
use Spiral\ORM\StateInterface;
use Spiral\ORM\Util\ContextStorage;

class PivotedRelation extends Relation\AbstractRelation
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
            $pivotData[$entity] = $this->orm->make($this->pivotEntity, $pivot, State::LOADED);
        }

        return [
            new PivotedCollection($elements, $pivotData),
            new ContextStorage($elements, $pivotData)
        ];
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

        $sequence = new Sequence();

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            $sequence->addCommand(
                $this->link($state, $item, $related->get($item), $related)
            );
        }

        // un-link old elements
        foreach ($original->getElements() as $item) {
            if (!$related->has($item)) {
                $sequence->addCommand(
                    $this->orm->queueDelete($original->get($item))
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
     * @param object         $pivot
     * @param ContextStorage $storage
     * @return CommandInterface
     */
    protected function link(StateInterface $state, $related, $pivot, ContextStorage $storage): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);
        $relState = $this->getState($related);
        $relState->addReference();

        if (!is_object($pivot)) {
            // first time initialization
            $pivot = $this->orm->make($this->pivotEntity, $pivot ?? []);
        }

        // defer the insert until pivot keys are resolved
        $pivotStore = $this->orm->queueStore($pivot);
        $pivotState = $this->getState($pivot);

        $this->promiseContext($pivotStore, $state, $this->innerKey, $pivotState, $this->thoughtInnerKey);
        $this->promiseContext($pivotStore, $relState, $this->outerKey, $pivotState, $this->thoughtOuterKey);

        $sequence = new Sequence();
        $sequence->addCommand($relStore);
        $sequence->addCommand($pivotStore);

        // update the link
        $storage->set($related, $pivot);

        return $sequence;
    }
}