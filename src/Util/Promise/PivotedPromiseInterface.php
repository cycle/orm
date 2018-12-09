<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util\Promise;

use Spiral\ORM\PromiseInterface;
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