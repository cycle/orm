<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Traits;

use Spiral\Cycle\Schema\Renderer;
use Spiral\Database\Database;
use Spiral\Database\ForeignKeyInterface;

trait TableTrait
{
    /**
     * @param string $table
     * @param array  $columns
     * @param array  $fk
     */
    public function makeTable(string $table, array $columns, array $fk = [], $pk = null, $defaults = [])
    {
        $schema = $this->getDatabase()->table($table)->getSchema();
        $renderer = new Renderer();
        $renderer->renderColumns($schema, $columns, $defaults);

        foreach ($fk as $column => $options) {
            $schema->foreignKey($column)->references($options['table'], $options['column']);
        }

        if (!empty($pk)) {
            $schema->setPrimaryKeys([$pk]);
        }

        $schema->save();
    }

    /**
     * @param string $from
     * @param string $fromKey
     * @param string $to
     * @param string $toTable
     * @param string $onDelete
     * @param string $onUpdate
     */
    public function makeFK(
        string $from,
        string $fromKey,
        string $to,
        string $toTable,
        string $onDelete = ForeignKeyInterface::CASCADE,
        string $onUpdate = ForeignKeyInterface::CASCADE
    ) {
        $schema = $this->getDatabase()->table($from)->getSchema();
        $schema->foreignKey($fromKey)->references($to, $toTable)->onDelete($onDelete)->onUpdate($onUpdate);
        $schema->save();
    }

    /**
     * @return Database
     */
    abstract protected function getDatabase(): Database;
}