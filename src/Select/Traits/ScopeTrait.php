<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
    /** @var null|ScopeInterface */
    protected $scope;

    public function getScope(): ?ScopeInterface
    {
        return $this->scope;
    }

    /**
     * Associate scope with the selector.
     * @param ScopeInterface|null $scope
     */
    public function setScope(?ScopeInterface $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * @deprecated Use {@see setScope()} instead.
     * @param ConstrainInterface|null $constrain
     * @return AbstractLoader|$this
     */
    public function setConstrain(?ConstrainInterface $constrain = null): self
    {
        $this->setScope($constrain);

        return $this;
    }

    /**
     * @param SelectQuery $query
     * @return SelectQuery
     */
    protected function applyScope(SelectQuery $query): SelectQuery
    {
        if ($this->scope !== null) {
            $this->scope->apply(new QueryBuilder($query, $this));
        }

        return $query;
    }
}
