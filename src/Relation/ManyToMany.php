<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Iterator;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\Collection\CollectionPromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\Pivoted;
use Cycle\ORM\Relation\Pivoted\PivotedStorage;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Doctrine\Common\Collections\Collection;
use IteratorAggregate;

class ManyToMany extends Relation\AbstractRelation
{
    /** @var string[] */
    protected array $throughInnerKeys;

    /** @var string[] */
    protected array $throughOuterKeys;

    protected ?string $pivotEntity = null;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $role, $name, $target, $schema);
        $this->pivotEntity = $this->schema[Relation::THROUGH_ENTITY] ?? null;

        $this->throughInnerKeys = (array)$this->schema[Relation::THROUGH_INNER_KEY];
        $this->throughOuterKeys = (array)$this->schema[Relation::THROUGH_OUTER_KEY];
    }

    public function init(Node $node, array $data): array
    {
        $elements = [];
        $pivotData = new \SplObjectStorage();

        $iterator = new Iterator($this->orm, $this->target, $data);
        foreach ($iterator as $pivot => $entity) {
            if (!is_array($pivot)) {
                // skip partially selected entities (DB level filter)
                continue;
            }

            $pivotData[$entity] = $this->orm->make($this->pivotEntity, $pivot, Node::MANAGED);
            $elements[] = $entity;
        }

        return [
            new Pivoted\PivotedCollection($elements, $pivotData),
            new PivotedStorage($elements, $pivotData)
        ];
    }

    public function extract($data): IteratorAggregate
    {
        if ($data instanceof CollectionPromiseInterface && !$data->isInitialized()) {
            return $data->getPromise();
        }

        if ($data instanceof Pivoted\PivotedCollectionInterface) {
            return new PivotedStorage($data->toArray(), $data->getPivotContext());
        }

        if ($data instanceof Collection) {
            return new PivotedStorage($data->toArray());
        }

        return new PivotedStorage();
    }

    public function initPromise(Node $node): array
    {
        $innerKeys = [];
        $nodeData = $node->getData();
        foreach ($this->innerKeys as $key) {
            if (!isset($nodeData[$key])) {
                return [new Pivoted\PivotedCollection(), null];
            }
            $innerKeys[$key] = $nodeData[$key];
        }

        // will take care of all the loading and scoping
        $p = new Pivoted\PivotedPromise(
            $this->orm,
            $this->target,
            $this->schema,
            $innerKeys
        );

        return [new Pivoted\PivotedCollectionPromise($p), $p];
    }

    public function prepare(Pool $pool, Tuple $tuple, bool $load = true): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());
        $related = $tuple->state->getRelation($this->getName());
        $related = $this->extract($related);
        // todo refactor
        $tuple->state->setStorage($this->pivotEntity . '?', $related);

        if ($original instanceof ReferenceInterface) {
            if (!$load && $related === $original && !$this->isResolved($original)) {
                return;
            }
            $original = $this->resolve($original);
            $node->setRelation($this->getName(), $original);
        }
        if (!$original instanceof PivotedStorage) {
            $original = $this->extract($original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
            $tuple->state->setRelation($this->getName(), $related);
        }

        // un-link old elements
        foreach ($original as $item) {
            if (!$related->has($item)) {
                $pivot = $original->get($item);
                $this->deleteChild($pool, $pivot, $item);
                $original->getContext()->offsetUnset($item);
            }
        }

        if (count($related) === 0) {
            $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return;
        }
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            // $sequence->addCommand(
            $this->newLink($pool, $tuple, $item, $related);
            // );
        }

    }
    public function queue(Pool $pool, Tuple $tuple): void
    {
        $related = $tuple->state->getStorage($this->pivotEntity . '?');
        // $related = $tuple->state->getRelation($this->getName());
        // $related = $this->extract($relatedSource);

        $node = $tuple->node;
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        // $original = $node->getRelation($this->getName()) ?? new PivotedStorage();
        // $original ??= new Pivoted\PivotedStorage();

        if ($related instanceof ReferenceInterface) {
            if (!$this->isResolved($related)) {
                return;
            }
        }

        $relationName = $this->getTargetRelationName();
        foreach ($related as $item) {
            $pivot = $related->get($item);
            $pTuple = $pool->offsetGet($pivot);
            $this->applyPivotChanges($tuple, $pTuple);
            $pTuple->node->setRelationStatus($relationName, RelationInterface::STATUS_RESOLVED);
        }
    }
    protected function applyPivotChanges(Tuple $parentTuple, Tuple $tuple): void
    {
        foreach ($this->innerKeys as $i => $innerKey) {
            $tuple->node->register($this->throughInnerKeys[$i], $parentTuple->state->getValue($innerKey));
        }
    }
    private function deleteChild(Pool $pool, ?object $pivot, object $child, ?Node $relatedNode = null): void
    {
        // todo: add support for nullable pivot entities?
        if ($pivot !== null) {
            $pool->attachDelete($pivot, $this->isCascade());
        }
        $pool->attachStore($child, false);
    }
    protected function newLink(Pool $pool, Tuple $tuple, object $related, PivotedStorage $storage): void
    {
        // $rStore = $this->orm->queueStore($related);
        $rNode = $this->getNode($related, +1);
        $this->assertValid($rNode);

        $pivot = $storage->get($related);
        if (!is_object($pivot)) {
            // first time initialization
            # todo
            $pivot = $this->initPivot($tuple->node, $related, $pivot);
            $storage->set($related, $pivot);
        }

        $pNode = $this->getNode($pivot);
        $pRelationName = $tuple->node->getRole() . ':' . $this->getName();
        // $pNode->setRelation($pRelationName, $tuple->state->getTransactionData());
        $pNode->setRelationStatus($pRelationName, RelationInterface::STATUS_RESOLVED);

        $pState = $pNode->getState();
        $rState = $rNode->getState();
        foreach ($this->throughInnerKeys as $i => $pInnerKey) {
            $pState->register($pInnerKey, $tuple->node->getState()->getTransactionData()[$this->innerKeys[$i]] ?? null, true);

            $rState->forward($this->outerKeys[$i], $pState, $this->throughOuterKeys[$i]);
        }

        $pool->attachStore($related, $this->isCascade(), $rNode, $rState);
        // defer the insert until pivot keys are resolved
        $pool->attachStore($pivot, $this->isCascade(), $pNode, $pState);
    }

    /**
     * Since many to many relation can overlap from two directions we have to properly resolve the pivot entity upon
     * it's generation. This is achieved using temporary mapping associated with each of the entity states.
     */
    protected function initPivot(Node $node, object $related, ?array $pivot): ?object
    {
        [$source, $target] = $this->sortRelation($node, $this->getNode($related));

        if ($source->getState()->getStorage($this->pivotEntity)->contains($target)) {
            return $source->getState()->getStorage($this->pivotEntity)->offsetGet($target);
        }

        $entity = $this->orm->make($this->pivotEntity, $pivot ?? []);

        $source->getState()->getStorage($this->pivotEntity)->offsetSet($target, $entity);

        return $entity;
    }

    /**
     * Keep only one relation branch as primary branch.
     *
     * @return Node[]
     */
    protected function sortRelation(Node $node, Node $related): array
    {
        // always use single storage
        if ($related->getState()->getStorage($this->pivotEntity)->contains($node)) {
            return [$related, $node];
        }

        return [$node, $related];
    }
}
