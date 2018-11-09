<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Database\DatabaseInterface;

interface ORMInterface
{
    public function getHeap(): HeapInterface;

    public function getFactory(): FactoryInterface;

    public function getSchema(): SchemaInterface;

    public function getDatabase(string $database): DatabaseInterface;
}