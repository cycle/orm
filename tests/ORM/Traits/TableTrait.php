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
     */
    public function makeTable(string $table, array $columns)
    {
        $schema = $this->getDatabase()->table($table)->getSchema();
        $renderer = new ColumnRenderer();
        $renderer->renderColumns($schema, $columns, []);
        $schema->save();
    }

    /**
     * @return Database
     */
    abstract protected function getDatabase(): Database;
}