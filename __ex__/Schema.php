<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

final class Schema
{
    public $database;
    public $table;

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getTable(): string
    {
        return $this->table;
    }
}