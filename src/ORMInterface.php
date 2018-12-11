<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Heap\HeapInterface;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\MapperInterface;
use Spiral\Database\DatabaseManager;

interface ORMInterface
{
    public function get(string $class, array $scope, bool $load = false);

    public function make(string $role, array $data, int $node = Node::NEW);

    public function getDBAL(): DatabaseManager;

    public function getMapper($entity): MapperInterface;

    public function getSchema(): SchemaInterface;

    public function getFactory(): FactoryInterface;

    public function getHeap(): ?HeapInterface;

    public function queueStore($entity, int $mode = 0): ContextCarrierInterface;

    public function queueDelete($entity, int $mode = 0): CommandInterface;
}