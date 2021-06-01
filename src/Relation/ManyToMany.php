<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Sequence;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
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

    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
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
        foreach ($this->innerKeys as $key) {
            $innerKey = $this->fetchKey($node, $key);
            if ($innerKey === null) {
                return [new Pivoted\PivotedCollection(), null];
            }
            $innerKeys[$key] = $innerKey;
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

    public function newQueue(Pool $pool, Tuple $tuple, $related): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName()) ?? new PivotedStorage();
        // $original ??= new Pivoted\PivotedStorage();

        ob_flush();
        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }
        ob_flush();

        // if ($original instanceof ReferenceInterface) {
        //     $original = $this->resolve($original);
        // }
        if (!$original instanceof PivotedStorage) {
            $original = $this->extract($original);
            // $node->setRelation($this->getName(), $original);
        }
        ob_flush();

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            // $sequence->addCommand(
            $this->newLink($pool, $tuple, $item, $related->get($item), $related);
            // );
        }

        // un-link old elements
        foreach ($original as $item) {
            if (!$related->has($item)) {
                // todo: add support for nullable pivot entities
                $pivot = $original->get($item);
                $pivot !== null and $pool->attachDelete($pivot, $this->isCascade());
                $pool->attachStore($item, false);
                if (!$original instanceof PivotedStorage) {
                    throw new \Exception('??');
                }
                $original->getContext()->offsetUnset($item);
            } else {
                echo 123;
            }
        }

        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        // return $sequence;
    }
    protected function newLink(Pool $pool, Tuple $tuple, object $related, $pivot, PivotedStorage $storage): void
    {
        // $rStore = $this->orm->queueStore($related);
        $rNode = $this->getNode($related, +1);
        $this->assertValid($rNode);

        $rTuple = $pool->attachStore($related, $this->isCascade(), $rNode);

        if (!is_object($pivot)) {
            // first time initialization
            $pivot = $this->initPivot($tuple->node, $related, $pivot);
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

        // defer the insert until pivot keys are resolved
        $pTuple = $pool->attachStore($pivot, $this->isCascade(), $pNode);

        // update the link
        $storage->set($related, $pivot);
    }

    /**
     * @inheritdoc
     *
     * @param PivotedStorage $related
     * @param PivotedStorage $original
     */
    public function queue(object $entity, Node $node, $related, $original): CommandInterface
    {
        $original ??= new PivotedStorage();

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        if ($original instanceof ReferenceInterface) {
            $original = $this->resolve($original);
        }

        $sequence = new Sequence();

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            $sequence->addCommand($this->link($node, $item, $related->get($item), $related));
        }

        // un-link old elements
        foreach ($original->getElements() as $item) {
            if (!$related->has($item)) {
                // todo: add support for nullable pivot entities
                $sequence->addCommand($this->orm->queueDelete($original->get($item)));
                $sequence->addCommand($this->orm->queueStore($item));
                $original->getContext()->offsetUnset($item);
            }
        }

        return $sequence;
    }

    /**
     * Link two entities together and create/update pivot context.
     *
     * @param object|array|null $pivot
     */
    protected function link(Node $node, object $related, $pivot, PivotedStorage $storage): CommandInterface
    {
        $rStore = $this->orm->queueStore($related);
        $rNode = $this->getNode($related, +1);
        $this->assertValid($rNode);

        if (!is_object($pivot)) {
            // first time initialization
            $pivot = $this->initPivot($node, $related, $pivot);
        }

        $pNode = $this->getNode($pivot);

        // defer the insert until pivot keys are resolved
        $pStore = $this->orm->queueStore($pivot);

        $this->forwardContext(
            $node,
            $this->innerKeys,
            $pStore,
            $pNode,
            $this->throughInnerKeys
        );

        $this->forwardContext(
            $rNode,
            $this->outerKeys,
            $pStore,
            $pNode,
            $this->throughOuterKeys
        );

        $sequence = new Sequence();
        $sequence->addCommand($rStore);
        $sequence->addCommand($pStore);

        // update the link
        $storage->set($related, $pivot);

        return $sequence;
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
