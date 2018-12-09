<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Selector;

use Spiral\ORM\Selector;

/**
 * Provides the ability to modify the selector.
 */
interface ScopeInterface
{
    /**
     * @param Selector $selector
     */
    public function apply(Selector $selector);
}