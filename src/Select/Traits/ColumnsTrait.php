<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Spiral\Database\Query\SelectQuery;

/**
 * Provides ability to add aliased columns into SelectQuery.
 */
trait ColumnsTrait
{
    /**
     * Return column name associated with given field.
     *
     * @param string $field
     * @return string
     */
    public function fieldAlias(string $field): string
    {
        return $this->getColumns()[$field] ?? $field;
    }

    /**
     * Set columns into SelectQuery.
     *
     * @param SelectQuery $query
     * @param bool        $minify    Minify column names (will work in case when query parsed in
     *                               FETCH_NUM mode).
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

        foreach ($this->getColumns() as $internal => $external) {
            $name = $external;
            if (!is_numeric($internal)) {
                $name = $internal;
            }

            $column = $name;

            if ($minify) {
                //Let's use column number instead of full name
                $column = 'c' . count($columns);
            }

            $columns[] = "{$alias}.{$external} AS {$prefix}{$column}";
        }

        return $query->columns($columns);
    }

    /**
     * Return original column names.
     *
     * @return array
     */
    protected function columnNames(): array
    {
        $result = [];
        foreach ($this->getColumns() as $internal => $external) {
            if (!is_numeric($internal)) {
                $result[] = $internal;
            } else {
                $result[] = $external;
            }
        }

        return $result;
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
