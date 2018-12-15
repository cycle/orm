<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector;

use Spiral\Database\Query\SelectQuery;

/**
 * Query builder and entity selector. Mocks SelectQuery.
 *
 * Trait provides the ability to transparently configure underlying loader query.
 *
 * @method QueryProxy distinct()
 * @method QueryProxy where(...$args);
 * @method QueryProxy andWhere(...$args);
 * @method QueryProxy orWhere(...$args);
 * @method QueryProxy having(...$args);
 * @method QueryProxy andHaving(...$args);
 * @method QueryProxy orHaving(...$args);
 * @method QueryProxy orderBy($expression, $direction = 'ASC');
 * @method QueryProxy limit(int $limit)
 * @method QueryProxy offset(int $offset)
 *
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 *
 * @todo IMPROVE!!! FOR SCOPES AND OTHER STUFF!
 */
class QueryProxy
{
    /** @var string */
    private $alias;

    /** @var null|SelectQuery */
    private $query;

    /** @var string|null */
    private $forward;

    /**
     * @param string $alias
     */
    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Link wrapper to the given target (query or selector).
     *
     * @param SelectQuery $target
     * @param string      $forward Automatically forward "where" queries to another target.
     * @return QueryProxy
     */
    public function withQuery(SelectQuery $target, string $forward = null): self
    {
        $wrapper = clone $this;
        $wrapper->query = $target;
        $wrapper->forward = $forward;

        return $wrapper;
    }

    /**
     * Get currently associated query.
     *
     * @return SelectQuery|null
     */
    public function getQuery(): ?SelectQuery
    {
        return $this->query;
    }

    /**
     * Forward call to underlying target.
     *
     * @param string $name
     * @param array  $args
     * @return SelectQuery|mixed
     */
    public function __call(string $name, array $args)
    {
        $args = array_values($args);
        if (count($args) > 0 && $args[0] instanceof \Closure) {
            call_user_func($args[0], $this);

            return $this->query;
        }

        // prepare arguments
        return call_user_func_array([$this->query, $this->forwardCall($name)], $this->prepare($args));
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
            if (strpos($where, '.') === false) {
                // always mount alias
                return sprintf("%s.%s", $this->alias, $where);
            }

            return str_replace('@', $this->alias, $where);
        }

        if (!is_array($where)) {
            return $where;
        }

        $result = [];
        foreach ($where as $column => $value) {
            if (is_string($column) && !is_int($column)) {
                $column = str_replace('@', $this->alias, $column);
            }

            $result[$column] = !is_array($value) ? $value : $this->prepare($value);
        }

        return $result;
    }

    /**
     * Replace target where call with another compatible method (for example join or having).
     *
     * @param string $call
     * @return string
     */
    protected function forwardCall(string $call): string
    {
        if ($this->forward != null) {
            switch (strtolower($call)) {
                case "where":
                    $call = $this->forward;
                    break;
                case "orwhere":
                    $call = 'or' . ucfirst($this->forward);
                    break;
                case "andwhere":
                    $call = 'and' . ucfirst($this->forward);
                    break;
            }
        }

        return $call;
    }
}