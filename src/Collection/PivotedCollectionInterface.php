<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Collection;

use Doctrine\Common\Collections\Collection;

interface PivotedCollectionInterface extends Collection
{
    /**
     * Return associated context between the values in collection
     * and parent entity.
     *
     * @return RelationContextInterface
     */
    public function getRelationContext(): RelationContextInterface;
}