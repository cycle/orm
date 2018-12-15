<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

use Spiral\Cycle\Mapper\MapperInterface;
use Spiral\Cycle\Relation\RelationInterface;
use Spiral\Cycle\Selector\LoaderInterface;
use Spiral\Database\DatabaseInterface;

interface FactoryInterface
{
    public function withContext(ORMInterface $orm, SchemaInterface $schema): FactoryInterface;

    public function database(string $name = null): DatabaseInterface;

    public function mapper(string $role): MapperInterface;

    public function loader(string $class, string $relation): LoaderInterface;

    public function relation(string $class, string $relation): RelationInterface;
}