<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select;

use Spiral\Cycle\ORMInterface;
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
 */
final class QueryProxy
{
    /** @var ORMInterface */
    private $orm;

    /** @var null|SelectQuery */
    private $query;

    /** @var AbstractLoader */
    private $loader;

    /** @var string|null */
    private $forward;

    /**
     * @param ORMInterface   $orm
     * @param SelectQuery    $query
     * @param AbstractLoader $loader
     */
    public function __construct(ORMInterface $orm, SelectQuery $query, AbstractLoader $loader)
    {
        $this->orm = $orm;
        $this->query = $query;
        $this->loader = $loader;
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
     * @return AbstractLoader
     */
    public function getLoader(): AbstractLoader
    {
        return $this->loader;
    }

    /**
     * Set query method prefix for all "where" queries. Can route "where" to "onWhere".
     *
     * @param string $forward
     * @return QueryProxy
     */
    public function setTarget(string $forward = null): self
    {
        $this->forward = $forward;

        return $this;
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
            // $query->where(function($q) { ...
            call_user_func($args[0], $this);
            return $this;
        }

        // prepare arguments
        $result = call_user_func_array($this->targetFunc($name), $this->proxy($args));
        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }

    /**
     * Helper function used to replace {@} alias with actual table name.
     *
     * @param mixed $where
     * @return mixed
     */
    protected function proxy($where)
    {
        // todo: this require a lot of tests

        if (is_string($where)) {
            if (strpos($where, '.') === false) {
                // always mount alias
                return sprintf("%s.%s", $this->loader->getAlias(), $where);
            }

            return str_replace('@', $this->loader->getAlias(), $where);
        }

        if (!is_array($where)) {
            return $where;
        }

        $result = [];
        foreach ($where as $column => $value) {
            if (is_string($column) && !is_int($column)) {
                $column = str_replace('@', $this->loader->getAlias(), $column);
            }

            $result[$column] = !is_array($value) ? $value : $this->proxy($value);
        }

        return $result;
    }

    /**
     * Replace target where call with another compatible method (for example join or having).
     *
     * @param string $call
     * @return callable
     */
    protected function targetFunc(string $call): callable
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

        return [$this->query, $call];
    }
}