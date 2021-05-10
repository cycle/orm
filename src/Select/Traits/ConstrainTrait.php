<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\QueryBuilder;
use Spiral\Database\Query\SelectQuery;

/**
 * Provides the ability to assign the scope to the AbstractLoader.
 */
trait ConstrainTrait
{
    protected ?ConstrainInterface $constrain = null;

    /**
     * Associate scope with the selector.
     *
     * @return AbstractLoader|$this
     */
    public function setConstrain(ConstrainInterface $constrain = null): self
    {
        $this->constrain = $constrain;

        return $this;
    }

    abstract public function getAlias(): string;

    protected function applyConstrain(SelectQuery $query): SelectQuery
    {
        if ($this->constrain !== null) {
            $this->constrain->apply(new QueryBuilder($query, $this));
        }

        return $query;
    }
}
