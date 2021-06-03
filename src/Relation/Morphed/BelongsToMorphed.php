<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BelongsTo;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

class BelongsToMorphed extends BelongsTo
{
    private string $morphKey;

    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->morphKey = $schema[Relation::MORPH_KEY];
    }

    public function initPromise(Node $node): array
    {
        $innerValues = [];
        foreach ($this->innerKeys as $i => $innerKey) {
            $innerValue = $this->fetchKey($node, $innerKey);
            if ($innerValue === null) {
                return [null, null];
            }
            $innerValues[] = $innerValue;
        }


        /** @var string $target */
        $target = $this->fetchKey($node, $this->morphKey);
        if ($target === null) {
            return [null, null];
        }

        $e = $this->orm->getHeap()->find($target, array_combine($this->outerKeys, $innerValues));
        if ($e !== null) {
            return [$e, $e];
        }

        $e = $this->orm->promise($target, array_combine($this->outerKeys, $innerValues));

        return [$e, $e];
    }

    public function newQueue(Pool $pool, Tuple $tuple, $related): void
    {
        $status = $tuple->node->getRelationStatus($this->getName());
        parent::newQueue($pool, $tuple, $related);

        if ($status !== Relation\RelationInterface::STATUS_PREPARE) {
            return;
        }
        $tuple->node->register(
            $this->morphKey,
            $related === null
                ? null
                : $this->getNode($related)->getRole(),
            true
        );
    }
    public function queue($entity, Node $node, $related, $original): CommandInterface
    {
        $wrappedStore = parent::queue($store, $entity, $node, $related, $original);

        if ($related === null) {
            if ($this->fetchKey($node, $this->morphKey) !== null) {
                $store->register($this->morphKey, null, true);
                $node->register($this->morphKey, null, true);
            }
        } else {
            $rNode = $this->getNode($related);
            if ($this->fetchKey($node, $this->morphKey) != $rNode->getRole()) {
                $store->register($this->morphKey, $rNode->getRole(), true);
                $node->register($this->morphKey, $rNode->getRole(), true);
            }
        }

        return $wrappedStore;
    }

    /**
     * Assert that given entity is allowed for the relation.
     *
     * @throws RelationException
     */
    protected function assertValid(Node $related): void
    {
        // no need to validate morphed relation yet
    }
}
