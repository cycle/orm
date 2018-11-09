<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Loader\LoaderInterface;

interface FactoryInterface
{
    public function withContext(ORMInterface $orm, SchemaInterface $schema): FactoryInterface;

    public function mapper(string $class): MapperInterface;

    public function source();

    public function selector(string $class);

    public function loader(): LoaderInterface;

    public function relation();
}