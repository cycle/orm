<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;
use Spiral\Database\Query\SelectQuery;

/**
 * Provides the ability to assign the scope to the AbstractLoader.
 */
trait ScopeTrait
{
    /** @var ScopeInterface|null */
    protected $constrain;

    abstract public function getAlias(): string;

    /**
     * Associate scope with the selector.
     *
     * @return $this|AbstractLoader
     */
    public function setScope(ScopeInterface $scope = null): self
    {
        $this->constrain = $scope;
        return $this;
    }

    /**
     * @deprecated Use {@see setScope()} instead.
     *
     * @return $this|AbstractLoader
     */
    public function setConstrain(ConstrainInterface $constrain = null): self
    {
        return $this->setScope($constrain);
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
