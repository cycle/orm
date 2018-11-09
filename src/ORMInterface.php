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
    public function getDatabase(string $database): DatabaseInterface;

    public function getSchema(): SchemaInterface;

    public function getFactory(): FactoryInterface;

    public function getHeap(): ?HeapInterface;

    public function makeEntity(string $class, array $data, int $state = Heap::STATE_NEW);
}