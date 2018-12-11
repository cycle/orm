<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Traits;

use Spiral\Database\Database;
use Spiral\Cycle\Schema\Renderer;

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
        $renderer = new Renderer();
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