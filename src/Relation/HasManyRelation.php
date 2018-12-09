<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Branch\Condition;
use Spiral\ORM\Command\Branch\Sequence;
use Spiral\ORM\Node;
use Spiral\ORM\PromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Util\Collection\CollectionPromise;
use Spiral\ORM\Util\Promise;

class HasManyRelation extends AbstractRelation
{
    use Traits\CollectionTrait;

    /**
     * @inheritdoc
     */
    public function initPromise(Node $point): array
    {
        if (empty($innerKey = $this->fetchKey($point, $this->innerKey))) {
            return [new ArrayCollection(), null];
        }

        // todo: where scope
        $p = new Promise\PromiseArray(
            $this->orm->getMapper($this->class)->getRepository(),
            [$this->outerKey => $innerKey] + ($this->define(Relation::WHERE_SCOPE) ?? []),
            $this->define(Relation::ORDER_BY) ?? []
        );

        return [new CollectionPromise($p), $p];
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        CarrierInterface $parentCommand,
        $parentEntity,
        Node $parentState,
        $related,
        $original
    ): CommandInterface {

        // todo: i can do quick compare here?
        // todo: why there is so many todos?

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
            $sequence->addCommand($this->queueStore($parentState, $item));
        }

        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $sequence->addCommand($this->queueDelete($parentState, $item));
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
     * @param Node   $parent
     * @param object $related
     * @return CarrierInterface
     */
    protected function queueStore(Node $parent, $related): CarrierInterface
    {
        $relStore = $this->orm->queueStore($related);
        $relState = $this->getPoint($related, +1);

        $this->addDependency($parent, $this->innerKey, $relStore, $relState, $this->outerKey);

        return $relStore;
    }

    /**
     * Remove one of related objects.
     *
     * @param Node   $parent
     * @param object $related
     * @return CommandInterface
     */
    protected function queueDelete(Node $parent, $related): CommandInterface
    {
        $origState = $this->getPoint($related);

        return new Condition(
            $this->orm->queueDelete($related),
            function () use ($origState) {
                return !$origState->getState()->hasClaims();
            }
        );
    }
}