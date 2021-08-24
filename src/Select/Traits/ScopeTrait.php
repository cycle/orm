<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;
use Spiral\Database\Query\SelectQuery;

/**
 * Provides the ability to assign the scope to the AbstractLoader.
 */
trait ScopeTrait
{
    /** @var null|ScopeInterface */
    protected $constrain;

    public function getScope(): ?ScopeInterface
    {
        return $this->constrain;
    }

    /**
     * Associate scope with the selector.
     */
    public function setScope(ScopeInterface $scope = null): void
    {
        $this->constrain = $scope;
    }

    /**
     * @deprecated Use {@see setScope()} instead.
     */
    public function setConstrain(ConstrainInterface $constrain = null): self
    {
        $this->setScope($constrain);

        return $this;
    }

    protected function applyScope(SelectQuery $query): SelectQuery
    {
        if ($this->constrain !== null) {
            $this->constrain->apply(new QueryBuilder($query, $this));
        }

        return $query;
    }

    /**
     * @deprecated Use {@see applyScope()} instead.
     */
    protected function applyConstrain(SelectQuery $query): SelectQuery
    {
        return $this->applyScope($query);
    }
}
