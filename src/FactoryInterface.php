<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM;

use Cycle\ORM\Mapper\MapperInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\LoaderInterface;

interface FactoryInterface
{
    public function withSchema(ORMInterface $orm, SchemaInterface $schema): FactoryInterface;

    public function mapper(string $role): MapperInterface;

    public function loader(string $class, string $relation): LoaderInterface;

    public function relation(string $class, string $relation): RelationInterface;
}