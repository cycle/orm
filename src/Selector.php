<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;


use Spiral\Treap\Loader\LoaderInterface;
use Spiral\Treap\Loader\RootLoader;

class Selector
{
    /** @var ORMInterface */
    private $orm;

    /** @var string */
    private $class;

    /** @var LoaderInterface */
    private $loader;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     */
    public function __construct(ORMInterface $orm, string $class)
    {
        $this->orm = $orm;
        $this->class = $class;
        $this->loader = new RootLoader($orm, $class);
    }

    /**
     * @return ORMInterface
     */
    public function getORM(): ORMInterface
    {
        return $this->orm;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Cloning with loader tree cloning.
     *
     * @attention at this moment binded query parameters would't be cloned!
     */
    public function __clone()
    {
        $this->loader = clone $this->loader;
    }

    /**
     * Remove nested loaders and clean ORM link.
     */
    public function __destruct()
    {
        $this->orm = null;
        $this->loader = null;
    }
}