<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select\Traits;

use Spiral\Cycle\Select\AbstractLoader;
use Spiral\Cycle\Select\ConstrainInterface;
use Spiral\Cycle\Select\QueryBuilder;
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
     * @return AbstractLoader
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
        if (!empty($this->constrain)) {
            $this->constrain->apply(new QueryBuilder($this->orm, $query, $this));
        }

        return $query;
    }
}