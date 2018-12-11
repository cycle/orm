<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Selector;

use Spiral\Database\Query\SelectQuery;

/**
 * Simple where and orderBy scope for the selections.
 *
 * @todo i want other scopes as well
 */
class Scope implements ScopeInterface
{
    /** @var array */
    private $where = [];

    /** @var array */
    private $orderBy = [];

    /**
     * @param array $where
     * @param array $orderBy
     */
    public function __construct(array $where, array $orderBy = [])
    {
        $this->where = $where;
        $this->orderBy = $orderBy;
    }

    /**
     * @inheritdoc
     */
    public function apply(SelectQuery $query): SelectQuery
    {
        return $query->where($this->where)->orderBy($this->orderBy);
    }

    public function loads(): array
    {
    }

    public function with(): array
    {
    }

}