<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

interface FactoryInterface
{
    public function withConfigured(ORMInterface $orm, SchemaInterface $schema): FactoryInterface;

    public function mapper(string $class): MapperInterface;

    public function loader(string $class, string $relation): LoaderInterface;

    public function relation(string $class, string $relation): RelationInterface;
}