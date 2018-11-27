<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util;

class PivotedPromise extends Promise
{
    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        return parent::__resolve()->getElements();
    }

    /**
     * Return promised pivot context.
     *
     * @return ContextStorage
     */
    public function __resolveContext(): ContextStorage
    {
        return parent::__resolve();
    }
}