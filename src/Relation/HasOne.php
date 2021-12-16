<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation\Traits\HasSomeTrait;
use Cycle\ORM\Relation\Traits\ToOneTrait;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Provides the ability to own and forward context values to child entity.
 *
 * @internal
 */
class HasOne extends AbstractRelation
{
    use HasSomeTrait;
    use ToOneTrait;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        $this->entityProvider = $orm->getService(EntityProviderInterface::class);

        parent::__construct($orm, $role, $name, $target, $schema);
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());
        $tuple->state->setRelation($this->getName(), $related);

        if ($original instanceof ReferenceInterface) {
            if (!$load && $this->compareReferences($original, $related)) {
                $original = $related instanceof ReferenceInterface ? $this->resolve($related, false) : $related;
                if ($original === null) {
                    // not found in heap
                    $node->setRelation($this->getName(), $related);
                    $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
                    return;
                }
            } else {
                $original = $this->resolve($original, true);
            }
            $node->setRelation($this->getName(), $original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related, true);
            $tuple->state->setRelation($this->getName(), $related);
        }

        if ($related === null) {
            $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            if ($original === null) {
                return;
            }
            $this->deleteChild($pool, $tuple, $original);
            return;
        }
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);

        $rTuple = $pool->attachStore($related, true);
        $this->assertValid($rTuple->node);

        if ($original !== null && $original !== $related) {
            $this->deleteChild($pool, $tuple, $original);
        }
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        if ($tuple->task !== Tuple::TASK_STORE) {
            return;
        }
        $related = $tuple->state->getRelation($this->getName());
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);

        if ($related instanceof ReferenceInterface && !$related->hasValue()) {
            return;
        }

        $rTuple = $pool->offsetGet($related);
        $this->applyChanges($tuple, $rTuple);
        $rTuple->state->setRelationStatus($this->getTargetRelationName(), RelationInterface::STATUS_RESOLVED);
    }
}
