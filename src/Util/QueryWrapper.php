<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util;

use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Selector;

/**
 * Proxy calls to underlying query to automatically calculate column aliases.
 */
class QueryWrapper
{
    /** @var string */
    private $alias;

    /** @var Selector|SelectQuery */
    private $target;

    /**
     * @param string $alias
     */
    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    /**
     * Link wrapper to the given target (query or selector).
     *
     * @param Selector|SelectQuery $target
     * @return QueryWrapper
     */
    public function withTarget($target): self
    {
        $wrapper = clone $this;
        $wrapper->target = $target;

        return $wrapper;
    }

    /**
     * Forward call to underlying target.
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        // prepare arguments
        return call_user_func_array([$this->target, $name], $this->prepare($arguments));
    }

    /**
     * Helper function used to replace {@} alias with actual table name.
     *
     * @param mixed $where
     * @return mixed
     */
    protected function prepare($where)
    {
        if (is_string($where)) {
            return str_replace(['{@}', '@'], $this->alias, $where);
        }

        if (!is_array($where)) {
            return $where;
        }

        $result = [];
        foreach ($where as $column => $value) {
            if (is_string($column) && !is_int($column)) {
                $column = str_replace(['{@}', '@'], $this->alias, $column);
            }

            $result[$column] = !is_array($value) ? $value : $this->prepare($value);
        }

        return $result;
    }
}