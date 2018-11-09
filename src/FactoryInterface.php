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
    public function withSchema(SchemaInterface $schema): FactoryInterface;

    public function mapper();

    public function source();

    public function selector();

    public function loader(): LoaderInterface;

    public function relation();
}