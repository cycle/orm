<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Command\Database\Traits;

trait WhereTrait
{
    /**
     * Where conditions (short where format).
     *
     * @var array
     */
    private $where = [];

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setWhere(string $key, $value)
    {
        $this->where[$key] = $value;
    }

    /**
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }
}