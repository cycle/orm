<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;


interface HeapInterface
{
    const STATE_NEW              = 0;
    const STATE_LOADED           = 1;
    const STATE_DELETED          = 2;
    const STATE_SCHEDULED        = 100;
    const STATE_SCHEDULED_INSERT = self::STATE_SCHEDULED | 4;
    const STATE_SCHEDULED_UPDATE = self::STATE_SCHEDULED | 5;
    const STATE_SCHEDULED_DELETE = self::STATE_SCHEDULED | 6;

    public function has(string $class, $entityID);

    public function get(string $class, $entityID);

    public function set($entity, $entityID, RelationMap $relmap = null);

    public function reset();
}