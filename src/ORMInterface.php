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
    public function getDatabase(string $class): DatabaseInterface;

    public function getMapper(string $class): MapperInterface;

    public function getRelationMap(string $class): RelationMap;

    public function getSchema(): SchemaInterface;

    public function getFactory(): FactoryInterface;

    public function getHeap(): ?HeapInterface;

    public function make(string $class, array $data, int $state = State::NEW);
}