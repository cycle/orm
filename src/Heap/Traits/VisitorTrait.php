<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

/**
 * Provides the ability to remember visited paths in dependency tree.
 */
trait VisitorTrait
{
    /** @var array @internal */
    private $visited = [];

    /**
     * Return true if relation branch was already visited.
     *
     * @param string $branch
     * @return bool
     */
    public function visited(string $branch): bool
    {
        return !empty($this->visited[$branch]);
    }

    /**
     * Indicate that relation branch has been visited.
     *
     * @param string $branch
     */
    public function markVisited(string $branch): void
    {
        $this->visited[$branch] = true;
    }
}
