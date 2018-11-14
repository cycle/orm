<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Traits;

use Spiral\Database\Database;
use Spiral\ORM\Schema\Util\ColumnRenderer;

trait TableTrait
{
    /**
     * @param string $table
     * @param array  $columns
     * @param array  $fk
     */
    public function makeTable(string $table, array $columns, array $fk = [])
    {
        $schema = $this->getDatabase()->table($table)->getSchema();
        $renderer = new ColumnRenderer();
        $renderer->renderColumns($schema, $columns, []);


        foreach ($fk as $column => $options) {
            $schema->foreignKey($column)->references($options['table'], $options['column']);
        }

        $schema->save();
    }

    /**
     * @return Database
     */
    abstract protected function getDatabase(): Database;
}