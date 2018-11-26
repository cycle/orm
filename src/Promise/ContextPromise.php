<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Promise;

use Spiral\ORM\Util\ContextStorage;

class ContextPromise extends Promise
{
    public function __resolve()
    {
        $response = parent::__resolve();

        return $response->getElements();
    }

    public function __resolveContext(): ContextStorage
    {
        return parent::__resolve();
    }
}