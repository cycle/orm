<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Control\Condition;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\StateInterface;

class HasManyRelation extends AbstractRelation
{
    use Traits\CollectionTrait;

    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $parent,
        $entity,
        StateInterface $state,
        $related,
        $original
    ): CommandInterface {
        $sequence = new Sequence();

        foreach ($related as $item) {
            $sequence->addCommand($this->queueStore($state, $item));
        }

        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $sequence->addCommand($this->queueDelete($state, $item));
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
     * @param StateInterface $parentState
     * @param object         $related
     * @return CommandInterface
     */
    protected function queueStore(StateInterface $parentState, $related): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);
        $relState = $this->getState($related);
        $relState->addReference();

        $this->promiseContext($relStore, $parentState, $this->innerKey, $relState, $this->outerKey);

        return $relStore;
    }

    /**
     * Remove one of related objects.
     *
     * @param StateInterface $parentState
     * @param object         $related
     * @return CommandInterface
     */
    protected function queueDelete(StateInterface $parentState, $related): CommandInterface
    {
        $origState = $this->getState($related);
        $origState->decReference();

        return new Condition(
            $this->orm->queueDelete($related),
            function () use ($origState) {
                return !$origState->hasReferences();
            }
        );
    }
}