<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

if (!function_exists('lazyload')) {
    function lazyload(&$object)
    {
        if ($object instanceof \Spiral\Treap\LazyLoaderInterface) {
            $object = $object->__resolveTarget();
        }

        return $object;
    }
}