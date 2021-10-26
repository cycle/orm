<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

interface ChangesCheckerInterface
{
    /**
     * Checks has relation value been changed.
     *
     * @param mixed $related
     * @param mixed $original
     *
     * @return bool
     */
    public function hasChanges($related, $original): bool;
}
