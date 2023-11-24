<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\Database\Query\SelectQuery;
use JetBrains\PhpStorm\Pure;

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
     * @var array<non-empty-string, non-empty-string>
     */
    protected array $columns = [];

    /**
     * Return column name associated with given field.
     */
    public function fieldAlias(string $field): ?string
    {
        // The field can be a JSON path separated by ->
        $p = \explode('->', $field, 2);

        $p[0] = $this->columns[$p[0]] ?? null;

        return $p[0] === null ? null : \implode('->', $p);
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
            $name = $internal;
            if ($minify) {
                //Let's use column number instead of full name
                $name = 'c' . \count($columns);
            }

            $columns[] = "{$alias}.{$external} AS {$prefix}{$name}";
        }

        return $query->columns($columns);
    }

    /**
     * Return original column names.
     */
    protected function columnNames(): array
    {
        return \array_keys($this->columns);
    }

    /**
     * Table alias of the loader.
     */
    abstract protected function getAlias(): string;

    /**
     * @param non-empty-string[] $columns
     *
     * @return array<non-empty-string, non-empty-string>
     *
     * @psalm-pure
     */
    #[Pure]
    private function normalizeColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $alias => $column) {
            $result[\is_int($alias) ? $column : $alias] = $column;
        }

        return $result;
    }
}
