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
        if ($object instanceof \Spiral\Treap\LazyloadableInterface) {
            $object = $object->__resolveTarget();
        }

        return $object;
    }
}

class User
{
    private $profile;

    public function getProfile(): Profile
    {
        return lazyload($this->profile);
    }
}

class Profile
{
}