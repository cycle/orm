<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector\Traits;

use Spiral\Cycle\Selector\AbstractLoader;
use Spiral\Cycle\Selector\QueryProxy;
use Spiral\Cycle\Selector\ScopeInterface;
use Spiral\Database\Query\SelectQuery;

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
    public function setScope(ScopeInterface $scope = null): self
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getAlias(): string;

    /**
     * @param SelectQuery $query
     * @return SelectQuery
     */
    protected function applyScope(SelectQuery $query): SelectQuery
    {
        if (!empty($this->scope)) {
            $this->scope->apply(new QueryProxy($query, $this));
        }

        return $query;
    }
}