<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Util;

use Cycle\ORM\Exception\SchemaException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractTable;

/**
 * Implements the ability to define column in AbstractSchema based on string representation and
 * default value (if defined).
 *
 * Attention, this class will try to guess default value if column is NOT NULL and no default
 * value provided by user.
 */
final class TableRenderer
{
    /**
     * Render columns in table based on string definition.
     *
     * Example:
     * renderColumns(
     *      $table,
     *      [
     *          'id'     => 'primary',
     *          'time'   => 'datetime, nullable',
     *          'status' => 'enum(active, disabled)'
     *      ],
     *      [
     *          'status' => 'active',
     *          'time'   => null,
     *      ]
     * );
     *
     * @param AbstractTable $table
     * @param array         $columns
     * @param array         $defaults
     *
     * @throws SchemaException
     */
    public function renderColumns(AbstractTable $table, array $columns, array $defaults): void
    {
        $primaryKeys = [];
        foreach ($columns as $name => $definition) {
            $type = $this->parse($table->getName(), $name, $definition);

            if ($this->hasFlag($type, 'primary')) {
                $primaryKeys[] = $name;
            }

            $this->renderColumn(
                $table->column($name),
                $type,
                array_key_exists($name, $defaults),
                $defaults[$name] ?? null
            );
        }

        if (count($primaryKeys)) {
            $table->setPrimaryKeys($primaryKeys);
        }
    }

    /**
     * Cast (specify) column schema based on provided column definition and default value.
     * Spiral will force default values (internally) for every NOT NULL column except primary keys!
     *
     * Column definition are compatible with database Migrations and AbstractColumn types.
     *
     * Column definition examples (by default all columns has flag NOT NULL):
     * const SCHEMA = [
     *      'id'           => 'primary',
     *      'name'         => 'string',                          //Default length is 255 characters.
     *      'email'        => 'string(255), nullable',           //Can be NULL
     *      'status'       => 'enum(active, pending, disabled)', //Enum values, trimmed
     *      'balance'      => 'decimal(10, 2)',
     *      'message'      => 'text, null',                      //Alias for nullable
     *      'time_expired' => 'timestamp'
     * ];
     *
     * Attention, column state will be affected!
     *
     * @param AbstractColumn $column
     * @param array          $type
     * @param bool           $hasDefault Must be set to true if default value was set by user.
     * @param mixed          $default    Default value declared by record schema.
     *
     * @throws SchemaException
     * @see  AbstractColumn
     */
    protected function renderColumn(AbstractColumn $column, array $type, bool $hasDefault, $default = null): void
    {
        // ORM force EVERY column to NOT NULL state unless different is said
        $column->nullable(false);

        if ($this->hasFlag($type, 'null') || $this->hasFlag($type, 'nullable')) {
            // indication that column is nullable
            $column->nullable(true);
        }

        try {
            // bypassing call to AbstractColumn->__call method (or specialized column method)
            call_user_func_array([$column, $type['type']], $type['options']);
        } catch (\Throwable $e) {
            throw new SchemaException(
                "Invalid column type definition in '{$column->getTable()}'.'{$column->getName()}'",
                $e->getCode(),
                $e
            );
        }

        if (in_array($column->getAbstractType(), ['primary', 'bigPrimary'])) {
            // no default value can be set of primary keys
            return;
        }

        if (!$hasDefault && !$column->isNullable()) {
            if (!$this->hasFlag($type, 'required') && !$this->hasFlag($type, 'primary')) {
                // we have to come up with some default value
                $column->defaultValue($this->castDefault($column));
            }

            return;
        }

        if (is_null($default)) {
            // default value is stated and NULL, clear what to do
            $column->nullable(true);
        }

        $column->defaultValue($default);
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $definition
     * @return array
     */
    protected function parse(string $table, string $column, string $definition): array
    {
        if (
            !preg_match(
                '/(?P<type>[a-z]+)(?: *\((?P<options>[^\)]+)\))?(?: *, *(?P<flags>.+))?/i',
                $definition,
                $type
            )
        ) {
            throw new SchemaException("Invalid column type definition in '{$table}'.'{$column}'");
        }

        if (empty($type['options'])) {
            $type['options'] = [];
        } else {
            $type['options'] = array_map('trim', explode(',', $type['options'] ?? ''));
        }

        if (empty($type['flags'])) {
            $type['flags'] = [];
        } else {
            $type['flags'] = array_map('trim', explode(',', $type['flags'] ?? ''));
        }

        unset($type[0], $type[1], $type[2], $type[3]);

        return $type;
    }

    /**
     * @param array  $type
     * @param string $flag
     * @return bool
     */
    protected function hasFlag(array $type, string $flag): bool
    {
        return in_array($flag, $type['flags'], true);
    }

    /**
     * Cast default value based on column type. Required to prevent conflicts when not nullable
     * column added to existed table with data in.
     *
     * @param AbstractColumn $column
     *
     * @return mixed
     */
    protected function castDefault(AbstractColumn $column)
    {
        if (in_array($column->getAbstractType(), ['timestamp', 'datetime', 'time', 'date'])) {
            return 0;
        }

        if ($column->getAbstractType() == 'enum') {
            // we can use first enum value as default
            return $column->getEnumValues()[0];
        }

        switch ($column->getType()) {
            case 'int':
                return 0;
            case 'float':
                return 0.0;
            case 'bool':
                return false;
        }

        return '';
    }
}
