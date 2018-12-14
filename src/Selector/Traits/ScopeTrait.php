<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector\Traits;

use Spiral\Cycle\Selector\AbstractLoader;
use Spiral\Cycle\Selector\ScopeInterface;

/**
 * Provides the ability to assign the scope to the AbstractLoader.
 */
trait ScopeTrait
{
    /** @var null|ScopeInterface */
    protected $scope;

    /**
     * Associate scope with the selector.
     *
     * @param ScopeInterface $scope
     * @return AbstractLoader
     */
    public function withScope(ScopeInterface $scope = null): self
    {
        $loader = clone $this;
        $loader->scope = $scope;

        return $loader;
    }
}