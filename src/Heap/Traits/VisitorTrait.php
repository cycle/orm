<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Heap\Traits;

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
        return array_key_exists($branch, $this->visited);
    }

    /**
     * Indicate that relation branch has been visited.
     *
     * @param string $branch
     */
    public function markVisited(string $branch)
    {
        $this->visited[$branch] = null;
    }
}