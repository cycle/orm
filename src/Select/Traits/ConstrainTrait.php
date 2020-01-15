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
use Spiral\Database\Query\SelectQuery;

/**
 * Provides the ability to assign the scope to the AbstractLoader.
 */
trait ConstrainTrait
{
    /** @var null|ConstrainInterface */
    protected $constrain;

    /**
     * Associate scope with the selector.
     *
     * @param ConstrainInterface $constrain
     * @return AbstractLoader|$this
     */
    public function setConstrain(ConstrainInterface $constrain = null): self
    {
        $this->constrain = $constrain;

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
    protected function applyConstrain(SelectQuery $query): SelectQuery
    {
        if ($this->constrain !== null) {
            $this->constrain->apply(new QueryBuilder($query, $this));
        }

        return $query;
    }
}
