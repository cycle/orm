<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

interface FactoryInterface
{
    public function withContext(ORMInterface $orm, SchemaInterface $schema): FactoryInterface;

    public function mapper(string $class): MapperInterface;

    public function source();

    public function selector(string $class);

    public function loader(string $class, string $relation): LoaderInterface;

    public function relation(string $class, string $relation): RelationInterface;
}