<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

if (!function_exists('promise')) {
    function promise(&$object)
    {
        if ($object instanceof \Spiral\Treap\PromiseInterface) {
            $object = $object->resolvePromise();
        }

        return $object;
    }
}