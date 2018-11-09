<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Loader\Traits;

use Spiral\Database\Query\SelectQuery;

/**
 * Provides ability to add aliased columns into SelectQuery.
 */
trait ColumnsTrait
{
    /**
     * Set columns into SelectQuery.
     *
     * @param SelectQuery $query
     * @param bool        $minify    Minify column names (will work in case when query parsed in FETCH_NUM mode).
     * @param string      $prefix    Prefix to be added for each column name.
     * @param bool        $overwrite When set to true existed columns will be removed.
     * @return SelectQuery
     */
    protected function mountColumns(
        SelectQuery $query,
        bool $minify = false,
        string $prefix = '',
        bool $overwrite = false
    ): SelectQuery {
        $alias = $this->getAlias();
        $columns = $overwrite ? [] : $query->getColumns();

        foreach ($this->getColumns() as $name) {
            $column = $name;

            if ($minify) {
                //Let's use column number instead of full name
                $column = 'c' . count($columns);
            }

            $columns[] = "{$alias}.{$name} AS {$prefix}{$column}";
        }

        return $query->columns($columns);
    }

    /**
     * Table alias of the loader.
     *
     * @return string
     */
    abstract protected function getAlias(): string;

    /**
     * List of columns associated with the loader.
     *
     * @return array
     */
    abstract protected function getColumns(): array;
}