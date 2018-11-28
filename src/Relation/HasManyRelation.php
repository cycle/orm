<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Collection\PromisedCollection;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Control\Condition;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\PromiseInterface;
use Spiral\ORM\State;
use Spiral\ORM\Util\Promise;

class HasManyRelation extends AbstractRelation
{
    use Traits\CollectionTrait;

    public function initPromise(State $state, $data): array
    {
        // todo: here we need paths (!)
        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return [null, null];
        }

        $pr = new Promise(
            [$this->outerKey => $innerKey],
            function () use ($innerKey) {
                // todo: where?
                return $this->orm->getMapper($this->class)->getRepository()->findAll([$this->outerKey => $innerKey]);
            }
        );

        return [new PromisedCollection($pr), $pr];
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $parent,
        $entity,
        State $state,
        $related,
        $original
    ): CommandInterface {

        // todo: i can do quick compare here?

        if ($related instanceof PromiseInterface) {
            // todo: resolve both original and related
            $related = $related->__resolve();
        }

        if ($original instanceof PromiseInterface) {
            // todo: check consecutive changes
            $original = $original->__resolve();
            // todo: state->setRelation (!!!!!!)
        }

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
     * @param State  $parentState
     * @param object $related
     * @return CommandInterface
     */
    protected function queueStore(State $parentState, $related): CommandInterface
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
     * @param State  $parentState
     * @param object $related
     * @return CommandInterface
     */
    protected function queueDelete(State $parentState, $related): CommandInterface
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