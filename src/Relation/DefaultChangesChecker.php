<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;

final class DefaultChangesChecker implements ChangesCheckerInterface
{
    public function hasChanges($related, $original): bool
    {
        if ($related instanceof PromiseInterface && $related->__loaded()) {
            return true;
        }

        if (null === $related && null === $original) {
            return false;
        }

        return !$this->sameReference($related, $original);
    }

    public function sameReference($a, $b): bool
    {
        if (!$a instanceof ReferenceInterface || !$b instanceof ReferenceInterface) {
            return false;
        }

        return $a->__role() === $b->__role() && $a->__scope() === $b->__scope();
    }
}
