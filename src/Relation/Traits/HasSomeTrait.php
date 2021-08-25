<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

trait HasSomeTrait
{
    /**
     * Delete original related entity of no other objects reference to it.
     */
    protected function deleteChild(Pool $pool, object $child): Tuple
    {
        if ($this->isNullable()) {
            $rTuple = $pool->attachStore($child, false);
            foreach ($this->outerKeys as $outerKey) {
                $rTuple->state->register($outerKey, null);
            }
            // todo: is it needed?
            // $rTuple->node->setRelationStatus($this->getTargetRelationName(), RelationInterface::STATUS_RESOLVED);
            return $rTuple;
        }
        return $pool->attachDelete($child, $this->isCascade());
    }

    /**
     * Apply inner key values to related entity
     */
    protected function applyChanges(Tuple $parentTuple, Tuple $tuple): void
    {
        foreach ($this->innerKeys as $i => $innerKey) {
            $tuple->node->register($this->outerKeys[$i], $parentTuple->state->getValue($innerKey));
        }
    }
}
