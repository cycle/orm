<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;
use Spiral\Treap\Loader\LoaderInterface;

class Factory implements FactoryInterface
{
    /** @var CoreFactory */
    private $factory;

    /** @var SchemaInterface */
    private $schema;

    /**
     * @param CoreFactory|null $factory
     */
    public function __construct(CoreFactory $factory = null)
    {
        $this->factory = $factory ?? new Container();
    }

    /**
     * @inheritdoc
     */
    public function withSchema(SchemaInterface $schema): FactoryInterface
    {
        $factory = clone $this;
        $factory->schema = $schema;

        return $factory;
    }

    public function mapper()
    {
        // TODO: Implement mapper() method.
    }

    public function source()
    {
        // TODO: Implement source() method.
    }

    public function selector()
    {
        // TODO: Implement selector() method.
    }

    public function loader(): LoaderInterface
    {
        // TODO: Implement loader() method.
    }

    public function relation()
    {
        // TODO: Implement relation() method.
    }
}