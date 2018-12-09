<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Traits;

use Spiral\ORM\Loader\RootLoader;

/**
 * Trait provides the ability to transparently configure underlying loader query.
 *
 * @method $this where(...$args);
 * @method $this andWhere(...$args);
 * @method $this orWhere(...$args);
 *
 * @method $this having(...$args);
 * @method $this andHaving(...$args);
 * @method $this orHaving(...$args);
 *
 * @method $this orderBy($expression, $direction = 'ASC');
 *
 * @method $this distinct()
 *
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 */
trait SelectTrait
{
    /**
     * Shortcut to where method to set AND condition for entity primary key.
     *
     * @param string|int $id
     * @return $this|self
     */
    public function wherePK($id): self
    {
        $this->getLoader()->getQuery()->where(
            $this->getLoader()->getPrimaryKey(),
            $id
        );

        return $this;
    }

    /**
     * Attention, column will be quoted by driver!
     *
     * @param string|null $column When column is null DISTINCT(PK) will be generated.
     * @return int
     */
    public function count(string $column = null): int
    {
        $loader = $this->getLoader();
        if (is_null($column)) {
            // @tuneyourserver solves the issue with counting on queries with joins.
            $column = "DISTINCT({$loader->getPrimaryKey()})";
        }

        return $loader->compileQuery()->count($column);
    }

    /**
     * Bypassing call to primary select query.
     *
     * @param string $name
     * @param array  $arguments
     * @return $this|mixed
     */
    public function __call(string $name, array $arguments)
    {
        $loader = $this->getLoader();

        // todo: alias wrapper
        if (in_array(strtoupper($name), ['AVG', 'MIN', 'MAX', 'SUM'])) {
            // one of aggregation requests
            $result = call_user_func_array([$loader->compileQuery(), $name], $arguments);
        } else {
            // where condition or statement
            $result = call_user_func_array([$loader->getQuery(), $name], $arguments);
        }

        if ($result === $loader->getQuery()) {
            return $this;
        }

        return $result;
    }

    /**
     * Base selection loader.
     *
     * @return RootLoader
     */
    abstract protected function getLoader(): RootLoader;
}