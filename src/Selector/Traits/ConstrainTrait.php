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
use Spiral\Cycle\Selector\ConstrainInterface;
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
     * @param ConstrainInterface $scope
     * @return AbstractLoader
     */
    public function setConstrain(ConstrainInterface $scope = null): self
    {
        $this->constrain = $scope;

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
        if (!empty($this->constrain)) {
            $this->constrain->apply(new QueryProxy($this->orm, $query, $this));
        }

        return $query;
    }
}