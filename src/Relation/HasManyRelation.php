<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Doctrine\Common\Collections\Collection;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Control\Condition;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

class HasManyRelation extends AbstractRelation
{
    use Relation\Traits\PromiseTrait;

    public const COLLECTION = true;

    public function extract($relData)
    {
        if ($relData instanceof Collection) {
            return $relData->toArray();
        }

        return $relData;
    }

    /**
     * @inheritdoc
     */
    public function queueRelation($entity, State $state, $related, $original): CommandInterface
    {
        $sequence = new Sequence();

        foreach ($related as $item) {
            $sequence->addCommand($this->store($state, $item));
        }

        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $sequence->addCommand($this->delete($state, $item));
        }

        return $sequence;
    }

    /**
     * Return objects which are subject of removal.
     *
     * @param array $related
     * @param array $original
     * @return array
     */
    protected function calcDeleted(array $related, array $original)
    {
        return array_udiff($original ?? [], $related, function ($a, $b) {
            return strcmp(spl_object_hash($a), spl_object_hash($b));
        });
    }

    /**
     * Persist related object.
     *
     * @param State  $parentState
     * @param object $related
     * @return CommandInterface
     */
    protected function store(State $parentState, $related): CommandInterface
    {
        $relStore = $this->orm->getMapper($related)->queueStore($related);

        $relState = $this->getState($related);
        $relState->addReference();

        $this->promiseContext(
            $relStore,
            $parentState,
            $this->define(Relation::INNER_KEY),
            $relState,
            $this->define(Relation::OUTER_KEY)
        );

        return $relStore;
    }

    /**
     * Remove one of related objects.
     *
     * @param State  $parentState
     * @param object $related
     * @return CommandInterface
     */
    protected function delete(State $parentState, $related): CommandInterface
    {
        $origState = $this->getState($related);
        $origState->decReference();

        return new Condition(
            $this->orm->getMapper($related)->queueDelete($related),
            function () use ($origState) {
                return !$origState->hasReferences();
            }
        );
    }
}