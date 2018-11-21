<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Traits;

/**
 * Provides the ability to remember visited paths in dependency tree.
 */
trait VisitorTrait
{
    /**
     * @invisible
     * @var array
     */
    private $visited = [];

    /**
     * Return true if relation branch was already visited.
     *
     * @param string $branch
     * @return bool
     */
    public function visited(string $branch): bool
    {
        return isset($this->visited[$branch]);
    }

    /**
     * Indicate that relation branch has been visited.
     *
     * @param string $branch
     */
    public function markVisited(string $branch)
    {
        $this->visited[$branch] = true;
    }

    /**
     * Reset all visited branches.
     */
    public function resetVisited()
    {
        $this->visited = [];
    }
}