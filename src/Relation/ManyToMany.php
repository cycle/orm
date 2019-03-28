<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
        $this->pivotEntity = $this->schema[Relation::THOUGH_ENTITY] ?? null;
        $this->thoughtInnerKey = $this->schema[Relation::THOUGH_INNER_KEY] ?? null;
        $this->thoughtOuterKey = $this->schema[Relation::THOUGH_OUTER_KEY] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function init(array $data): array
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
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [new Pivoted\PivotedCollection(), null];
        }

        // will take care of all the loading and scoping
        $p = new Pivoted\PivotedPromise($this->orm, $this->target, $this->schema, $innerKey);

        return [new Pivoted\PivotedCollectionPromise($p), $p];
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

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        if ($original instanceof ReferenceInterface) {
            $original = $this->resolve($original);
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
        $this->assertValid($relNode);

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