<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation;

/**
 * Identical to RelationInterface but defines "left" side of the graph (relation to parent objects).
 */
interface DependencyInterface extends RelationInterface
{
}
