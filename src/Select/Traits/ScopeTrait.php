<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;

/**
 * Provides the ability to assign the scope to the AbstractLoader.
 *
 * @internal
 */
trait ScopeTrait
{
    protected ?ScopeInterface $scope = null;

    /**
     * Associate scope with the selector.
     */
    public function setScope(?ScopeInterface $scope = null): self
    {
        $this->scope = $scope;

        return $this;
    }

    abstract public function getAlias(): string;

    protected function applyScope(SelectQuery $query): SelectQuery
    {
        $this->scope?->apply(new QueryBuilder($query, $this));

        return $query;
    }
}
