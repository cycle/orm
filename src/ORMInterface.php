<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface;

interface ORMInterface
{
    public function get(string $class, array $scope, bool $load = false);

    public function make(string $role, array $data, int $node = Node::NEW);

    public function getDBAL(): DatabaseManager;

    public function getDatabase($entity): DatabaseInterface;

    public function getMapper($entity): MapperInterface;

    public function getSchema(): SchemaInterface;

    public function getFactory(): FactoryInterface;

    public function getHeap(): ?HeapInterface;


    public function queueStore($entity, int $mode = 0): ContextCarrierInterface;

    public function queueDelete($entity, int $mode = 0): CommandInterface;
}