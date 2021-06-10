<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasOne;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Inverted version of belongs to morphed.
 */
class MorphedHasOne extends HasOne
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

        $scope = array_combine($this->outerKeys, $innerValues) + [$this->morphKey => $node->getRole()];

        $r = $this->orm->promise($this->target, $scope);
        return [$r, $r];
    }

    public function queue(Pool $pool, Tuple $tuple, $related): void
    {
        parent::queue($pool, $tuple, $related);
        $node = $tuple->node;
        if ($related !== null) {
            $rNode = $this->getNode($related);

            if ($this->fetchKey($rNode, $this->morphKey) !== $node->getRole()) {
                // $rStore->register($this->morphKey, $node->getRole(), true);
                $rNode->register($this->morphKey, $node->getRole(), true);
            }
        }

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
