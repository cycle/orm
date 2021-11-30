<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\EmptyReference;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BelongsTo;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * @internal
 */
class BelongsToMorphed extends BelongsTo
{
    private string $morphKey;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $role, $name, $target, $schema);
        $this->morphKey = $schema[Relation::MORPH_KEY];
    }

    public function initReference(Node $node): ReferenceInterface
    {
        $scope = $this->getReferenceScope($node);
        $nodeData = $node->getData();
        if ($scope === null || !isset($nodeData[$this->morphKey])) {
            $result = new Reference($node->getRole(), []);
            $result->setValue(null);
            return $result;
        }
        // $scope[$this->morphKey] = $nodeData[$this->morphKey];
        $target = $nodeData[$this->morphKey];

        return $scope === [] ? new EmptyReference($target, null) : new Reference($target, $scope);
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        parent::prepare($pool, $tuple, $related, $load);
        $related = $tuple->state->getRelation($this->getName());

        if ($related === null) {
            return;
        }

        $role = $related instanceof ReferenceInterface
            ? $related->getRole()
            : $pool->offsetGet($related)?->node->getRole();
        $tuple->state->register($this->morphKey, $role);
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
