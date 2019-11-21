<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
use Doctrine\Common\Collections\Collection;

class ManyToMany extends Relation\AbstractRelation
{

    /** @var string */
    protected $throughInnerKey;

    /** @var string */
    protected $throughOuterKey;
    /** @var string|null */
    private $pivotEntity;

    /**
     * @param ORMInterface $orm
     * @param string       $name
     * @param string       $target
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->pivotEntity = $this->schema[Relation::THROUGH_ENTITY] ?? null;
        $this->throughInnerKey = $this->schema[Relation::THROUGH_INNER_KEY] ?? null;
        $this->throughOuterKey = $this->schema[Relation::THROUGH_OUTER_KEY] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function init(Node $node, array $data): array
    {
        $elements = [];
        $pivotData = new \SplObjectStorage();

        $iterator = new Iterator($this->orm, $this->target, $data);
        foreach ($iterator as $pivot => $entity) {
            $pivotData[$entity] = $this->orm->make($this->pivotEntity, $pivot, Node::MANAGED);
            $elements[] = $entity;
        }

        return [
            new Pivoted\PivotedCollection($elements, $pivotData),
            new Pivoted\PivotedStorage($elements, $pivotData)
        ];
    }

    /**
     * @inheritdoc
     */
    public function extract($data)
    {
        if ($data instanceof CollectionPromiseInterface && !$data->isInitialized()) {
            return $data->getPromise();
        }

        if ($data instanceof Pivoted\PivotedCollectionInterface) {
            return new Pivoted\PivotedStorage($data->toArray(), $data->getPivotContext());
        }

        if ($data instanceof Collection) {
            return new Pivoted\PivotedStorage($data->toArray());
        }

        return new Pivoted\PivotedStorage();
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $node): array
    {
        $innerKey = $this->fetchKey($node, $this->innerKey);
        if ($innerKey === null) {
            return [new Pivoted\PivotedCollection(), null];
        }

        // will take care of all the loading and scoping
        $p = new Pivoted\PivotedPromise(
            $this->orm,
            $this->target,
            $this->schema,
            $innerKey
        );

        return [new Pivoted\PivotedCollectionPromise($p), $p];
    }

    /**
     * @inheritdoc
     *
     * @param Pivoted\PivotedStorage $related
     * @param Pivoted\PivotedStorage $original
     */
    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface
    {
        $original = $original ?? new Pivoted\PivotedStorage();

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
            }
        }

        return $sequence;
    }

    /**
     * Link two entities together and create/update pivot context.
     *
     * @param Node                   $node
     * @param object                 $related
     * @param object                 $pivot
     * @param Pivoted\PivotedStorage $storage
     * @return CommandInterface
     */
    protected function link(Node $node, $related, $pivot, Pivoted\PivotedStorage $storage): CommandInterface
    {
        $rStore = $this->orm->queueStore($related);
        $rNode = $this->getNode($related, +1);
        $this->assertValid($rNode);

        if (!is_object($pivot)) {
            // first time initialization
            $pivot = $this->initPivot($node, $related, $pivot);
        }

        // defer the insert until pivot keys are resolved
        $pStore = $this->orm->queueStore($pivot);
        $pNode = $this->getNode($pivot);

        $this->forwardContext(
            $node,
            $this->innerKey,
            $pStore,
            $pNode,
            $this->throughInnerKey
        );

        $this->forwardContext(
            $rNode,
            $this->outerKey,
            $pStore,
            $pNode,
            $this->throughOuterKey
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
     *
     * @param Node   $node
     * @param object $related
     * @param mixed  $pivot
     * @return mixed|object|null
     */
    protected function initPivot(Node $node, $related, $pivot)
    {
        $relNode = $this->getNode($related);
        if ($node->getState()->getStorage($this->pivotEntity)->contains($relNode)) {
            return $node->getState()->getStorage($this->pivotEntity)->offsetGet($relNode);
        }

        $entity = $this->orm->make($this->pivotEntity, $pivot ?? []);

        $node->getState()->getStorage($this->pivotEntity)->offsetSet($relNode, $entity);
        $relNode->getState()->getStorage($this->pivotEntity)->offsetSet($node, $entity);

        return $entity;
    }
}
