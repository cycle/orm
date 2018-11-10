<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Schema;

use Spiral\Database\Schema\AbstractTable;

interface TableInterface
{
    public function declare(AbstractTable $table);
}