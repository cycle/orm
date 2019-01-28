<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Relation\Traits;

use Spiral\Cycle\Exception\RelationException;
use Spiral\Cycle\Heap\Node;

trait MorphedTrait
{
    /**
     * Assert that given entity is allowed for the relation.
     *
     * @param object $related
     * @param Node   $relNode
     *
     * @throws RelationException
     */
    protected function assertValid($related, Node $relNode)
    {
        // no need to validate morphed relation yet
    }
}