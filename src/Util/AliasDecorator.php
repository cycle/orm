<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util;

use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Selector;

/**
 * AliasDecorator used to mock user functions and route where() calls to specified destination
 * (where, onWhere, etc). This functionality used to describe WHERE conditions in ORM loaders using
 * unified where syntax.
 *
 * Decorator can additionally decorate target table name, using magic expression "{@}". Table name
 * decoration is required as Loader target table can be unknown for user.
 *
 * @deprecated
 */
class AliasDecorator
{
    /**
     * Target function postfix. All requests will be routed using this pattern and "or", "and"
     * prefixes.
     *
     * @var string
     */
    protected $target = 'where';

    /**
     * Decorator will replace {@} with this alias in every where column.
     *
     * @var string
     */
    protected $alias = '';

    /**
     * Decorated query builder.
     *
     * @var SelectQuery|Selector
     */
    protected $query = null;

    /**
     * @param SelectQuery|Selector $query
     * @param string               $target
     * @param string               $alias
     */
    public function __construct($query, string $target = 'where', string $alias = '')
    {
        $this->query = $query;
        $this->target = $target;
        $this->alias = $alias;
    }

    /**
     * Update target method all where requests should be router into.
     *
     * @param string $target
     */
    public function setTarget(string $target)
    {
        $this->target = $target;
    }

    /**
     * Get active routing target.
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Simple WHERE condition with various set of arguments. Routed to where/on/having based
     * on decorator settings.
     *
     * @see \Spiral\Database\Query\Traits\WhereTrait
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return $this|self
     *
     * @throws BuilderException
     */
    public function where(...$args): AliasDecorator
    {
        if ($args[0] instanceof \Closure) {
            call_user_func($args[0], $this);

            return $this;
        }

        //We have to prepare only first argument
        $args[0] = $this->prepare($args[0]);

        //Routing where
        call_user_func_array([$this->query, $this->target], $args);

        return $this;
    }

    /**
     * @param array $orderBy In a form [expression => direction]
     * @return self
     */
    public function orderBy(array $orderBy): AliasDecorator
    {
        $this->query->orderBy($this->prepare($orderBy));

        return $this;
    }

    /**
     * Simple AND WHERE condition with various set of arguments. Routed to where/on/having based
     * on decorator settings.
     *
     * @see \Spiral\Database\Query\Traits\WhereTrait
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return $this|self
     *
     * @throws BuilderException
     */
    public function andWhere(...$args): AliasDecorator
    {
        if ($args[0] instanceof \Closure) {
            call_user_func($args[0], $this);

            return $this;
        }

        //We have to prepare only first argument
        $args[0] = $this->prepare($args[0]);

        //Routing where
        call_user_func_array([$this->query, 'and' . ucfirst($this->target)], $args);

        return $this;
    }

    /**
     * Simple OR WHERE condition with various set of arguments. Routed to where/on/having based
     * on decorator settings.
     *
     * @see \Spiral\Database\Query\Traits\WhereTrait
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return $this|self
     *
     * @throws BuilderException
     */
    public function orWhere(...$args): AliasDecorator
    {
        if ($args[0] instanceof \Closure) {
            call_user_func($args[0], $this);

            return $this;
        }

        //We have to prepare only first argument
        $args[0] = $this->prepare($args[0]);

        //Routing where
        call_user_func_array([$this->query, 'or' . ucfirst($this->target)], $args);

        return $this;
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