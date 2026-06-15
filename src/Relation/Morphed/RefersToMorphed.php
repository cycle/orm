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
use Cycle\ORM\Relation\RefersTo;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Morphed variation of the {@see RefersTo} relation. Like {@see BelongsToMorphed} it stores
 * both the outer key and the target role (morph key) on the owner, but inherits the deferred,
 * "soft" dependency resolution of {@see RefersTo}. This allows the relation to be self linked
 * and to participate in cyclic references (A > A, A > B > A) that {@see BelongsToMorphed} can
 * not persist in a single transaction.
 *
 * @internal
 */
class RefersToMorphed extends RefersTo
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
        if (!isset($nodeData[$this->morphKey], $scope)) {
            return new EmptyReference('?', null);
        }
        $target = $nodeData[$this->morphKey];

        return $scope === [] ? new EmptyReference($target, null) : new Reference($target, $scope);
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        // The parent RefersTo resets the node relation while handling a null value, so capture
        // whether there was a related entity before delegating.
        $hadRelation = $tuple->node->getRelation($this->getName()) !== null;
        parent::prepare($pool, $tuple, $related, $load);
        $this->syncMorphKey($pool, $tuple, $hadRelation);
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        $hadRelation = $tuple->node->getRelation($this->getName()) !== null;
        parent::queue($pool, $tuple);
        $this->syncMorphKey($pool, $tuple, $hadRelation);
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

    /**
     * Keep the morph key in sync with the related entity role. The role is known as soon as the
     * related object is available, so it can be registered eagerly even while the outer key is
     * still deferred by the parent {@see RefersTo} logic.
     */
    private function syncMorphKey(Pool $pool, Tuple $tuple, bool $hadRelation): void
    {
        $relName = $this->getName();
        $state = $tuple->state;
        if (!$state->hasRelation($relName)) {
            return;
        }

        $related = $state->getRelation($relName);

        if ($related === null) {
            // Reset the morph key when the relation was changed to null
            if ($hadRelation) {
                $state->register($this->morphKey, null);
            }
            return;
        }

        if ($related instanceof EmptyReference) {
            return;
        }

        $role = $related instanceof ReferenceInterface
            ? $related->getRole()
            : $pool->offsetGet($related)?->node->getRole();

        $state->register($this->morphKey, $role);
    }
}
