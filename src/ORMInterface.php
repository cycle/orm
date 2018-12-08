<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\Database\DatabaseInterface;
use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\CommandInterface;

interface ORMInterface
{
    public function locateOne(string $class, array $scope, bool $load = false);

    public function getDatabase($entity): DatabaseInterface;

    public function getMapper($entity): MapperInterface;

    public function getSchema(): SchemaInterface;

    public function getFactory(): FactoryInterface;

    public function getHeap(): ?HeapInterface;

    public function make(string $class, array $data, int $state = Point::NEW);

    public function queueStore($entity, int $mode = 0): CarrierInterface;

    public function queueDelete($entity, int $mode = 0): CommandInterface;
}