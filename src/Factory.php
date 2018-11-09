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

    /** @var ORMInterface */
    private $orm;

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
    public function withContext(ORMInterface $orm, SchemaInterface $schema): FactoryInterface
    {
        $factory = clone $this;
        $factory->orm = $orm;
        $factory->schema = $schema;

        return $factory;
    }

    public function mapper(string $class): MapperInterface
    {
        // TODO: Implement mapper() method.
    }

    public function source()
    {
        // TODO: Implement source() method.
    }

    /**
     * @inheritdoc
     */
    public function selector(string $class)
    {
        return new Selector($this->orm, $class);
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