<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Loader;


use Spiral\Treap\Exception\LoaderException;
use Spiral\Treap\Node\AbstractNode;
use Spiral\Treap\ORMInterface;

class AbstractLoader implements LoaderInterface
{
    // Loading methods for data loaders.
    public const INLOAD    = 1;
    public const POSTLOAD  = 2;
    public const JOIN      = 3;
    public const LEFT_JOIN = 4;

    /** @var ORMInterface */
    protected $orm;

    /** @var string */
    protected $class;

    /** @var array */
    protected $options = [];

    /** @var LoaderInterface[] */
    protected $load = [];

    /**
     * Set of loaders with ability to JOIN it's data into parent SelectQuery.
     *
     * @var AbstractLoader[]
     */
    protected $join = [];

    /**
     * @invisible
     * @var AbstractLoader
     */
    protected $parent;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     */
    public function __construct(ORMInterface $orm, string $class)
    {
        $this->orm = $orm;
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface
    {
        if (!$parent instanceof AbstractLoader) {
            throw new LoaderException(
                sprintf("Loader of type '%s' can not accept parent '%s'", get_class($this), get_class($parent))
            );
        }

        /*
         * This scary construction simply checks if input array has keys which do not present in a
         * current set of options (i.e. default options, i.e. current options).
         */
        if (!empty($wrong = array_diff(array_keys($options), array_keys($this->options)))) {
            throw new LoaderException(
                sprintf("Relation %s does not support option: %s", get_class($this), join(',', $wrong))
            );
        }

        $loader = clone $this;
        $loader->parent = $parent;
        $loader->options = $options + $this->options;

        return $loader;
    }

    /**
     * {@inheritdoc}
     */
    final public function createNode(): AbstractNode
    {
        $node = $this->initNode();

        //Working with nested relation loaders
        foreach ($this->load as $relation => $loader) {
            $node->registerNode($relation, $loader->createNode());
        }

        return $node;
    }

    /**
     * @param AbstractNode $node
     */
    public function loadData(AbstractNode $node)
    {
        //Loading data thought child loaders
        foreach ($this->load as $relation => $loader) {
            $loader->loadData($node->fetchNode($relation));
        }
    }

    /**
     * Ensure state of every nested loader.
     */
    public function __clone()
    {
        foreach ($this->load as $name => $loader) {
            $this->load[$name] = $loader->withContext($this);
        }

        foreach ($this->join as $name => $loader) {
            $this->join[$name] = $loader->withContext($this);
        }
    }

    /**
     * Destruct loader.
     */
    final public function __destruct()
    {
        $this->load = [];
        $this->join = [];
    }
}