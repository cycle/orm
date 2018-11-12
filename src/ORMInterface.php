<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\Database\DatabaseInterface;

interface ORMInterface
{
    public function getDatabase($entity): DatabaseInterface;

    public function getMapper($entity): MapperInterface;

    public function getRelationMap($entity): RelationMap;

    public function getSchema(): SchemaInterface;

    public function getFactory(): FactoryInterface;

    public function getHeap(): ?HeapInterface;

    public function make(string $class, array $data, int $state = State::NEW);
}