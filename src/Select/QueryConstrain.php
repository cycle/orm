<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select;

/**
 * Provides the ability to scope query and load necessary relations into the loader.
 */
final class QueryConstrain implements ConstrainInterface
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
    public function apply(QueryProxy $proxy)
    {
        $proxy->where($this->where)->orderBy($this->orderBy);
    }
}