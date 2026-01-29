<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasOne;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Inverted version of belongs to morphed.
 *
 * @internal
 */
class MorphedHasOne extends HasOne
{
    private string $morphKey;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $role, $name, $target, $schema);
        $this->morphKey = $schema[Relation::MORPH_KEY];
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        parent::queue($pool, $tuple);
        $related = $tuple->state->getRelation($this->getName());
        // todo: make test when $related is instance of Reference
        if ($related === null || $related instanceof ReferenceInterface) {
            return;
        }
        $rTuple = $pool->offsetGet($related);
        if ($rTuple === null) {
            return;
        }

        $data = $rTuple->state->getData();

        $role = $tuple->node->getRole();
        if (($data[$this->morphKey] ?? null) !== $role) {
            $rTuple->state->register($this->morphKey, $role);
        }
    }

    protected function getReferenceScope(Node $node): ?array
    {
        return parent::getReferenceScope($node) + [$this->morphKey => $node->getRole()];
    }

    protected function getTargetRelationName(): string
    {
        return '~morphed~:' . $this->name;
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
