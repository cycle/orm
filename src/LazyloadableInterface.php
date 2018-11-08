<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

// name: TBD
interface LazyloadableInterface
{
    public function __resolveTarget();
}