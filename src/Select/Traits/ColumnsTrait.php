<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\Database\Query\SelectQuery;

/**
 * Provides ability to add aliased columns into SelectQuery.
 *
 * @internal
 */
trait ColumnsTrait
{
    /**
     * List of columns associated with the loader.
     *
     * @var string[]
     */
    protected array $columns;

    /**
     * Return column name associated with given field.
     */
    public function fieldAlias(string $field): string
    {
        return $this->columns[$field] ?? $field;
    }

    /**
     * Set columns into SelectQuery.
     *
     * @param bool        $minify    Minify column names (will work in case when query parsed in
     *                               FETCH_NUM mode).
     * @param string      $prefix    Prefix to be added for each column name.
     * @param bool        $overwrite When set to true existed columns will be removed.
     */
    protected function mountColumns(
        SelectQuery $query,
        bool $minify = false,
        string $prefix = '',
        bool $overwrite = false
    ): SelectQuery {
        $alias = $this->getAlias();
        $columns = $overwrite ? [] : $query->getColumns();

        foreach ($this->columns as $internal => $external) {
            $name = $external;
            if (!\is_numeric($internal)) {
                $name = $internal;
            }

            $column = $name;

            if ($minify) {
                //Let's use column number instead of full name
                $column = 'c' . \count($columns);
            }

            $columns[] = "{$alias}.{$external} AS {$prefix}{$column}";
        }

        return $query->columns($columns);
    }

    /**
     * Return original column names.
     */
    protected function columnNames(): array
    {
        $result = [];
        foreach ($this->columns as $internal => $external) {
            if (!\is_numeric($internal)) {
                $result[] = $internal;
            } else {
                $result[] = $external;
            }
        }

        return $result;
    }

    /**
     * Table alias of the loader.
     */
    abstract protected function getAlias(): string;
}
