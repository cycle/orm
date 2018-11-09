<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Exception\MapperException;

interface MapperInterface
{
    /**
     * Construct entity.
     *
     * @param array       $data
     * @param RelationMap $relmap
     * @return object
     *
     * @throws MapperException
     */
    public function make(array $data, RelationMap $relmap = null);
}