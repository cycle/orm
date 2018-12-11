<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Promise;

use Spiral\ORM\Util\ContextStorage;

/**
 * Provides lazy load access to pivoted data.
 */
interface PivotedPromiseInterface extends PromiseInterface
{
    /**
     * Return promised pivot context.
     *
     * @return ContextStorage
     */
    public function __resolveContext(): ContextStorage;
}