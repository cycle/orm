<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Traits;

use Cycle\ORM\Tests\Util\TableRenderer;
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
        $renderer = new TableRenderer();
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