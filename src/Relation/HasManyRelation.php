<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Spiral\Cycle\Command\Branch\Condition;
use Spiral\Cycle\Command\Branch\Sequence;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface as CC;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Promise\Collection\CollectionPromise;
use Spiral\Cycle\Promise\PromiseInterface;
use Spiral\Cycle\Promise\PromiseMany;
use Spiral\Cycle\Relation;

/**
 * Provides the ability to own the collection of entities.
 */
class HasManyRelation extends AbstractRelation
{
    /**
     * Init relation state and entity collection.
     *
     * @param array $data
     * @return array
     */
    public function init(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            $result[] = $this->orm->make($this->target, $item, Node::MANAGED);
        }

        return [new ArrayCollection($result), $result];
    }

    /**
     * Convert entity data into array.
     *
     * @param mixed $data
     * @return array|PromiseInterface
     */
    public function extract($data)
    {
        if ($data instanceof CollectionPromise && !$data->isInitialized()) {
            return $data->getPromise();
        }

        if ($data instanceof Collection) {
            return $data->toArray();
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [new ArrayCollection(), null];
        }

        $p = new PromiseMany(
            $this->orm,
            $this->target,
            [
                $this->outerKey => $innerKey
            ],
            $this->schema[Relation::WHERE] ?? []
        );
        $p->setScope($this->getScope());

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