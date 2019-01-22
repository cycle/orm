<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

use Spiral\Cycle\Mapper\MapperInterface;
use Spiral\Cycle\Relation\RelationInterface;
use Spiral\Cycle\Select\LoaderInterface;

interface FactoryInterface
{
    public function withSchema(ORMInterface $orm, SchemaInterface $schema): FactoryInterface;

    public function mapper(string $role): MapperInterface;

    public function loader(string $class, string $relation): LoaderInterface;

    public function relation(string $class, string $relation): RelationInterface;
}