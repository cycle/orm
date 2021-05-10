<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Condition;
use Cycle\ORM\Command\Branch\Sequence;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\Collection\CollectionPromise;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\PromiseMany;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Provides the ability to own the collection of entities.
 */
class HasMany extends AbstractRelation
{
    /**
     * Init relation state and entity collection.
     */
    public function init(Node $node, array $data): array
    {
        $elements = [];
        foreach ($data as $item) {
            $elements[] = $this->orm->make($this->target, $item, Node::MANAGED);
        }

        return [new ArrayCollection($elements), $elements];
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

    public function initPromise(Node $node): array
    {
        $innerValues = [];
        foreach ($this->innerKeys as $i => $innerKey) {
            $innerValue = $this->fetchKey($node, $innerKey);
            if ($innerValue === null) {
                return [new ArrayCollection(), null];
            }
            $innerValues[] = $innerValue;
        }

        $p = new PromiseMany(
            $this->orm,
            $this->target,
            array_combine($this->outerKeys, $innerValues),
            $this->schema[Relation::WHERE] ?? []
        );

        return [new CollectionPromise($p), $p];
    }

    public function queue(CC $store, object $entity, Node $node, $related, $original): CommandInterface
    {
        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        if ($original instanceof ReferenceInterface) {
            $original = $this->resolve($original);
        }

        $sequence = new Sequence();

        foreach ($related as $item) {
            $sequence->addCommand($this->queueStore($node, $item));
        }

        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $sequence->addCommand($this->queueDelete($item));
        }

        return $sequence;
    }

    /**
     * Return objects which are subject of removal.
     */
    protected function calcDeleted(array $related, array $original): array
    {
        return array_udiff($original ?? [], $related, fn($a, $b) => strcmp(spl_object_hash($a), spl_object_hash($b)));
    }

    /**
     * Persist related object.
     */
    protected function queueStore(Node $node, object $related): CC
    {
        $relStore = $this->orm->queueStore($related);
        $relNode = $this->getNode($related, +1);
        $this->assertValid($relNode);

        $this->forwardContext(
            $node,
            $this->innerKeys,
            $relStore,
            $relNode,
            $this->outerKeys
        );

        return $relStore;
    }

    /**
     * Remove one of related objects.
     */
    protected function queueDelete(object $related): CommandInterface
    {
        $rNode = $this->getNode($related);

        if ($this->isNullable()) {
            $store = $this->orm->queueStore($related);
            foreach ($this->outerKeys as $key) {
                $store->register($this->columnName($rNode, $key), null, true);
            }
            $rNode->getState()->decClaim();

            return new Condition($store, fn() => !$rNode->getState()->hasClaims());
        }

        return new Condition($this->orm->queueDelete($related), fn() => !$rNode->getState()->hasClaims());
    }
}
