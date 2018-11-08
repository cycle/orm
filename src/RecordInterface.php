<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Models\EntityInterface;
use Spiral\Treap\Relation\RelationMap;

interface RecordInterface extends EntityInterface
{
    /**
     * Pack entity data into array form, no accessors is allowed. Not typed strictly to be
     * compatible with AccessorInterface.
     *
     * @return array
     */
    public function packValue(): array;

    /**
     * Return all associated record related values.
     *
     * ????
     *
     * @return RelationMap
     */
    public function getRelationMap(): RelationMap;
}