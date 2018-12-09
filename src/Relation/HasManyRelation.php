<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\Command\Branch\Condition;
use Spiral\ORM\Command\Branch\Sequence;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface as CC;
use Spiral\ORM\Node;
use Spiral\ORM\PromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Util\Collection\CollectionPromise;
use Spiral\ORM\Util\Promise;

/**
 * Provides the ability to own the collection of entities.
 */
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

        $p = new Promise\PromiseMany(
            $this->getMapper(),
            array_merge([$this->outerKey => $innerKey], $this->define(Relation::WHERE_SCOPE) ?? []),
            $this->schema[Relation::ORDER_BY] ?? []
        );

        return [new CollectionPromise($p), $p];
    }

    /**
     * @inheritdoc
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        if ($related instanceof PromiseInterface) {
            $related = $related->__resolve();
        }

        if ($original instanceof PromiseInterface) {
            $original = $original->__resolve();
        }

        $sequence = new Sequence();

        foreach ($related as $item) {
            $sequence->addCommand($this->queueStore($parentNode, $item));
        }

        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $sequence->addCommand($this->queueDelete($item));
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
     * @param Node   $parentNode
     * @param object $related
     * @return CC
     */
    protected function queueStore(Node $parentNode, $related): CC
    {
        $relStore = $this->orm->queueStore($related);
        $relNode = $this->getNode($related, +1);

        $this->forwardContext(
            $parentNode,
            $this->innerKey,
            $relStore,
            $relNode,
            $this->outerKey
        );

        return $relStore;
    }

    /**
     * Remove one of related objects.
     *
     * @param object $related
     * @return CommandInterface
     */
    protected function queueDelete($related): CommandInterface
    {
        $relNode = $this->getNode($related);

        return new Condition($this->orm->queueDelete($related), function () use ($relNode) {
            return !$relNode->getState()->hasClaims();
        });
    }
}