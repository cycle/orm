<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasMany;
use Cycle\ORM\Transaction\Tuple;

/**
 * @internal
 */
class MorphedHasMany extends HasMany
{
    private string $morphKey;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $role, $name, $target, $schema);
        $this->morphKey = $schema[Relation::MORPH_KEY];
    }

    protected function getReferenceScope(Node $node): ?array
    {
        $scope = parent::getReferenceScope($node);
        return $scope === null ? null : $scope + [$this->morphKey => $node->getRole()];
    }

    protected function getTargetRelationName(): string
    {
        return '~morphed~:' . $this->name;
    }

    protected function applyChanges(Tuple $parentTuple, Tuple $tuple): void
    {
        parent::applyChanges($parentTuple, $tuple);

        $rState = $tuple->state;
        $role = $parentTuple->node->getRole();
        if (($rState->getData()[$this->morphKey] ?? null) !== $role) {
            $rState->register($this->morphKey, $role);
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
