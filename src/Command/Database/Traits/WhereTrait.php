<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Command\Database\Traits;

trait WhereTrait
{
    /**
     * Where conditions (short where format).
     *
     * @var array
     */
    private $where = [];

    /**
     * @param array $where
     */
    public function setWhere(array $where)
    {
        $this->where = $where;
    }

    /**
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }
}