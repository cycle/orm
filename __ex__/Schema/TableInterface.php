<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Schema;

use Spiral\Database\Schema\AbstractTable;

interface TableInterface
{
    public function declare(AbstractTable $table);
}