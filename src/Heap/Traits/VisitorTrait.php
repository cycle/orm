<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

/**
 * Provides the ability to remember visited paths in dependency tree.
 */
trait VisitorTrait
{
    /** @internal */
    private array $visited = [];

    /**
     * Return true if relation branch was already visited.
     */
    public function visited(string $branch): bool
    {
        return !empty($this->visited[$branch]);
    }

    /**
     * Indicate that relation branch has been visited.
     */
    public function markVisited(string $branch): void
    {
        $this->visited[$branch] = true;
    }
}
