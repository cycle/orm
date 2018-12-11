<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\ManyToMany;

use Doctrine\Common\Collections\Collection;
use Spiral\ORM\Command\Branch\Sequence;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface as CC;
use Spiral\ORM\Heap\Node;
use Spiral\ORM\Iterator;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Promise\PivotedPromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Util\Collection\PivotedCollection;
use Spiral\ORM\Util\Collection\PivotedCollectionPromise;
use Spiral\ORM\Util\Collection\PivotedInterface;
use Spiral\ORM\Util\ContextStorage;

class PivotedRelation extends Relation\AbstractRelation
{
    /** @var string|null */
    private $pivotEntity;

    /** @var string */
    protected $thoughtInnerKey;

    /** @var string */
    protected $thoughtOuterKey;

    /**
     * @param ORMInterface $orm
     * @param string       $name
     * @param string       $target
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->pivotEntity = $this->schema[Relation::PIVOT_ENTITY] ?? null;
        $this->thoughtInnerKey = $this->schema[Relation::THOUGHT_INNER_KEY] ?? null;
        $this->thoughtOuterKey = $this->schema[Relation::THOUGHT_OUTER_KEY] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function init(array $data): array
    {
        $elements = [];
        $pivotData = new \SplObjectStorage();

        foreach (new Iterator($this->orm, $this->target, $data) as $pivot => $entity) {
            $elements[] = $entity;
            $pivotData[$entity] = $this->orm->make($this->pivotEntity, $pivot, Node::MANAGED);
        }

        return [new PivotedCollection($elements, $pivotData), new ContextStorage($elements, $pivotData)];
    }

    /**
     * @inheritdoc
     */
    public function extract($data)
    {
        if ($data instanceof PivotedCollectionPromise && !$data->isInitialized()) {
            return $data->toPromise();
        }

        if ($data instanceof PivotedInterface) {
            return new ContextStorage($data->toArray(), $data->getPivotContext());
        }

        if ($data instanceof Collection) {
            return new ContextStorage($data->toArray());
        }

        return new ContextStorage();
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [null, null];
        }

        // will take care of all the loading and scoping
        $p = new PivotedPromise($this->orm, $this->target, $this->schema, $innerKey);

        return [new PivotedCollectionPromise($p), $p];
    }

    /**
     * @inheritdoc
     *
     * @param ContextStorage $related
     * @param ContextStorage $original
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        $original = $original ?? new ContextStorage();

        if ($related instanceof PivotedPromiseInterface) {
            $related = $related->__resolveContext();
        }

        if ($original instanceof PivotedPromiseInterface) {
            $original = $original->__resolveContext();
        }

        $sequence = new Sequence();

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            $sequence->addCommand($this->link($parentNode, $item, $related->get($item), $related));
        }

        // un-link old elements
        foreach ($original->getElements() as $item) {
            if (!$related->has($item)) {
                $sequence->addCommand($this->orm->queueDelete($original->get($item)));
            }
        }

        return $sequence;
    }

    /**
     * Link two entities together and create/update pivot context.
     *
     * @param Node           $parentNode
     * @param object         $related
     * @param object         $pivot
     * @param ContextStorage $storage
     * @return CommandInterface
     */
    protected function link(Node $parentNode, $related, $pivot, ContextStorage $storage): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);
        $relNode = $this->getNode($related, +1);

        if (!is_object($pivot)) {
            // first time initialization
            $pivot = $this->orm->make($this->pivotEntity, $pivot ?? []);
        }

        // defer the insert until pivot keys are resolved
        $pivotStore = $this->orm->queueStore($pivot);
        $pivotNode = $this->getNode($pivot);

        $this->forwardContext(
            $parentNode,
            $this->innerKey,
            $pivotStore,
            $pivotNode,
            $this->thoughtInnerKey
        );

        $this->forwardContext(
            $relNode,
            $this->outerKey,
            $pivotStore,
            $pivotNode,
            $this->thoughtOuterKey
        );

        $sequence = new Sequence();
        $sequence->addCommand($relStore);
        $sequence->addCommand($pivotStore);

        // update the link
        $storage->set($related, $pivot);

        return $sequence;
    }
}