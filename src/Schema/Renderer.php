<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema;

use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Cycle\Exception\Schema\DeclarationException;

/**
 * Implements the ability to define column in AbstractSchema based on string representation and
 * default value (if defined).
 *
 * Attention, this class will try to guess default value if column is NOT NULL and no default
 * value provided by user.
 */
final class Renderer
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
     * @throws DeclarationException
     */
    public function renderColumns(AbstractTable $table, array $columns, array $defaults)
    {
        foreach ($columns as $name => $definition) {
            $this->renderColumn(
                $table->column($name),
                $definition,
                array_key_exists($name, $defaults),
                $defaults[$name] ?? null
            );
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
     * @see  AbstractColumn
     * @param AbstractColumn $column
     * @param string         $definition
     * @param bool           $hasDefault Must be set to true if default value was set by user.
     * @param mixed          $default    Default value declared by record schema.
     *
     * @throws DeclarationException
     */
    protected function renderColumn(
        AbstractColumn $column,
        string $definition,
        bool $hasDefault,
        $default = null
    ) {
        //        if (
        //            class_exists($definition) && is_a($definition, ColumnInterface::class, true)
        //        ) {
        //            // dedicating column definition to our column class
        //            call_user_func([$definition, 'describeColumn'], $column);
        //            return;
        //        }

        $pattern = '/(?P<type>[a-z]+)(?: *\((?P<options>[^\)]+)\))?(?: *, *(?P<nullable>null(?:able)?))?/i';

        if (!preg_match($pattern, $definition, $type)) {
            throw new DeclarationException(
                "Invalid column type definition in '{$column->getTable()}'.'{$column->getName()}'"
            );
        }

        if (!empty($type['options'])) {
            // exporting and trimming
            $type['options'] = array_map('trim', explode(',', $type['options']));
        }

        // ORM force EVERY column to NOT NULL state unless different is said
        $column->nullable(false);

        if (!empty($type['nullable'])) {
            // indication that column is nullable
            $column->nullable(true);
        }

        try {
            // bypassing call to AbstractColumn->__call method (or specialized column method)
            call_user_func_array(
                [$column, $type['type']],
                !empty($type['options']) ? $type['options'] : []
            );
        } catch (\Throwable $e) {
            throw new DeclarationException(
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
            // we have to come up with some default value
            $column->defaultValue($this->castDefault($column));

            return;
        }

        if (is_null($default)) {
            // default value is stated and NULL, clear what to do
            $column->nullable(true);
        }

        $column->defaultValue($default);
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

        switch ($column->phpType()) {
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