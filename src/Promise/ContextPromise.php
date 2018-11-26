<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Promise;

class ContextPromise extends Promise
{
    public function __resolve()
    {
        $response = parent::__resolve();

        return $response->getElements();
    }

    public function __resolveContext()
    {
        return parent::__resolve();
    }
}