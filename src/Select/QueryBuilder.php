<?php
declare(strict_types=1);
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
 * @method QueryBuilder distinct()
 * @method QueryBuilder where(...$args);
 * @method QueryBuilder andWhere(...$args);
 * @method QueryBuilder orWhere(...$args);
 * @method QueryBuilder having(...$args);
 * @method QueryBuilder andHaving(...$args);
 * @method QueryBuilder orHaving(...$args);
 * @method QueryBuilder orderBy($expression, $direction = 'ASC');
 * @method QueryBuilder limit(int $limit)
 * @method QueryBuilder offset(int $offset)
 *
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 *
 * @todo make it smarter
 */
final class QueryBuilder
{
    /** @var ORMInterface @internal */
    private $orm;

    /** @var SelectQuery */
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
     * Select query method prefix for all "where" queries. Can route "where" to "onWhere".
     *
     * @param string $forward "where", "onWhere"
     * @return QueryBuilder
     */
    public function setForwarding(string $forward = null): self
    {
        $this->forward = $forward;

        return $this;
    }

    /**
     * @param SelectQuery $query
     * @return QueryBuilder
     */
    public function withQuery(SelectQuery $query): self
    {
        $builder = clone $this;
        $builder->query = $query;

        return $builder;
    }

    /**
     * Forward call to underlying target.
     *
     * @param string $func
     * @param array  $args
     * @return SelectQuery|mixed
     */
    public function __call(string $func, array $args)
    {
        // prepare arguments
        $result = call_user_func_array($this->targetFunc($func), $this->resolve($func, $args));

        if ($result === $this->query) {
            return $this;
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

    /**
     * Automatically modify all identifiers to mount table prefix. Provide ability to automatically resolve
     * relations.
     *
     * @param array $args
     * @return array
     */
    protected function resolve($func, array $args): array
    {


        // short array syntax
        if (count($args) === 1 && array_key_exists(0, $args) && is_array($args[0])) {
            return $this->walk_recursive($args, function (&$k, &$v) {
                if (
                    !is_numeric($k)
                    // todo: improve
                    && !in_array(strtoupper($k), ['@OR', '@AND', '<', '>', '<=', '>=', 'IN', 'BETWEEN', 'LIKE'])
                ) {
                    $k = $this->resolveColumn($k);
                }

                if ($v instanceof \Closure) {
                    $v = function ($q) use ($v) {
                        $v($this->withQuery($q));
                    };
                }
            });
        }

        // short syntax (first argument is identifier)
        if (array_key_exists(0, $args) && is_string($args[0])) {
            $args[0] = $this->resolveColumn($args[0]);
        }

        if (array_key_exists(0, $args) && $args[0] instanceof \Closure) {
            $args[0] = function ($q) use ($args) {
                $args[0]($this->withQuery($q));
            };
        }

        return $args;
    }

    private function walk_recursive(array $input, callable $function, $level = 0): array
    {
        $result = [];
        foreach ($input as $k => $v) {
            if (is_array($v)) {
                $v = $this->walk_recursive($v, $function, $level + 1);
            }

            call_user_func_array($function, [&$k, &$v, $level]);
            $result[$k] = $v;
        }

        return $result;
    }

    /**
     * Automatically resolve identifier.
     *
     * todo: make this method public
     *
     * @param string $identifier
     * @return string
     */
    public function resolveColumn(string $identifier): string
    {
        if (strpos($identifier, '.') === false) {
            // parent element
            return sprintf('%s.%s', $this->loader->getAlias(), $this->loader->columnName($identifier));
        }

        // todo: automatic relation load?

        $chunks = explode('.', $identifier);

        if (count($chunks) == 2) {
            if (in_array($chunks[0], [$this->loader->getAlias(), $this->loader->getTarget(), '@'])) {
                return sprintf(
                    "%s.%s.",
                    $this->loader->getAlias(),
                    $this->loader->columnName($chunks[1])
                );
            }
        }

        // todo must be improved
        if (count($chunks) >= 2 && strpos($identifier, '(') == false) {
            $column = array_pop($chunks);
            $loader = $this->loader->loadRelation(join('.', $chunks), [], true);

            return sprintf('%s.%s', $loader->getAlias(), $loader->columnName($column));
        }

        // strict format (?)
        return str_replace('@', $this->loader->getAlias(), $identifier);
    }
}
