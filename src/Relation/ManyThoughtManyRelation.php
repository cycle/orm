<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Relation;

use Spiral\Cycle\Command\Branch\Sequence;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface as CC;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Iterator;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Promise\PromiseInterface;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Relation\Pivoted;

class ManyThoughtManyRelation extends Relation\AbstractRelation
{
    use Relation\Traits\PivotedTrait;

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
     *
     * @param Pivoted\PivotedStorage $related
     * @param Pivoted\PivotedStorage $original
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        $original = $original ?? new Pivoted\PivotedStorage();

        if ($related instanceof PromiseInterface) {
            $related = $related->__resolve();
        }

        if ($original instanceof PromiseInterface) {
            $original = $original->__resolve();
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
     * @param Node                   $parentNode
     * @param object                 $related
     * @param object                 $pivot
     * @param Pivoted\PivotedStorage $storage
     * @return CommandInterface
     */
    protected function link(Node $parentNode, $related, $pivot, Pivoted\PivotedStorage $storage): CommandInterface
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