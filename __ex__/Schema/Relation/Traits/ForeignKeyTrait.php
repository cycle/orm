<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Schema\Relation\Traits;

use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractTable;

/**
 * Simplified functionality to create foreign for a given schema.
 */
trait ForeignKeyTrait
{
    /**
     * @param AbstractTable  $table
     * @param AbstractColumn $source
     * @param AbstractColumn $target
     * @param string         $onDelete
     * @param string         $onUpdate
     */
    protected function createForeign(
        AbstractTable $table,
        AbstractColumn $source,
        AbstractColumn $target,
        string $onDelete,
        string $onUpdate
    ) {
        $foreignKey = $table->foreignKey($source->getName());

        $foreignKey->references($target->getTable(), $target->getName(), false);
        $foreignKey->onDelete($onDelete);
        $foreignKey->onUpdate($onUpdate);
    }
}