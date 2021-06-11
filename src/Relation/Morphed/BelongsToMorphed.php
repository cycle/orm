<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

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
        $nodeData = $node->getData();
        foreach ($this->innerKeys as $innerKey) {
            if (!isset($nodeData[$innerKey])) {
                return [null, null];
            }
            $innerValues[] = $nodeData[$innerKey];
        }


        if (!isset($nodeData[$this->morphKey])) {
            return [null, null];
        }
        /** @var string $target */
        $target = $nodeData[$this->morphKey];

        $e = $this->orm->getHeap()->find($target, array_combine($this->outerKeys, $innerValues));
        if ($e !== null) {
            return [$e, $e];
        }

        $e = $this->orm->promise($target, array_combine($this->outerKeys, $innerValues));

        return [$e, $e];
    }

    public function queue(Pool $pool, Tuple $tuple, $related): void
    {
        $status = $tuple->node->getRelationStatus($this->getName());
        parent::queue($pool, $tuple, $related);

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
