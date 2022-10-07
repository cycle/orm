<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * @internal
 */
trait HasSomeTrait
{
    /**
     * Delete original related entity of no other objects reference to it.
     */
    protected function deleteChild(Pool $pool, Tuple $tuple, object $child): Tuple
    {
        if ($this->isNullable()) {
            $rTuple = $pool->attachStore($child, false);
            $relName = $this->getTargetRelationName();
            // Related state
            $state = $rTuple->state;
            if (!$state->hasRelation($relName) || $state->getRelation($relName) === $tuple->entity) {
                foreach ($this->outerKeys as $outerKey) {
                    $state->register($outerKey, null);
                }
                $state->setRelation($relName, null);
            }
            return $rTuple;
        }
        return $pool->attachDelete($child, $this->isCascade());
    }

    /**
     * Apply inner key values to related entity
     *
     * @param Tuple $tuple Related tuple
     */
    protected function applyChanges(Tuple $parentTuple, Tuple $tuple): void
    {
        foreach ($this->innerKeys as $i => $innerKey) {
            $tuple->state->register($this->outerKeys[$i], $parentTuple->state->getValue($innerKey));
        }
    }
}
