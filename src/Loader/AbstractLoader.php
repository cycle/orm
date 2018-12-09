<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Loader;

use Spiral\Database\DatabaseInterface;
use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Exception\FactoryException;
use Spiral\ORM\Exception\LoaderException;
use Spiral\ORM\Loader\Traits\ChainTrait;
use Spiral\ORM\LoaderInterface;
use Spiral\ORM\TreeGenerator\AbstractNode;
use Spiral\ORM\ORMInterface;

/**
 * ORM Loaders used to load an compile data tree based on results fetched from SQL databases,
 * loaders can communicate with parent selector by providing it's own set of conditions, columns
 * joins and etc. In some cases loader may create additional selector to load data using information
 * fetched from previous query.
 *
 * Attention, AbstractLoader can only work with ORM Records, you must implement LoaderInterface
 * in order to support external references (MongoDB and etc).
 *
 * Loaders can be used for both - loading and filtering of record data.
 *
 * Reference tree generation logic example:
 *  User has many Posts (relation "posts"), user primary is ID, post inner key pointing to user
 *  is USER_ID. Post loader must request User data loader to create references based on ID field
 *  values. Once Post data were parsed we can mount it under parent user using mount method:
 *
 * @see Selector::load()
 * @see Selector::with()
 */
abstract class AbstractLoader implements LoaderInterface
{
    use ChainTrait;

    // Loading methods for data loaders.
    public const INLOAD    = 1;
    public const POSTLOAD  = 2;
    public const JOIN      = 3;
    public const LEFT_JOIN = 4;

    /** @var ORMInterface @invisible */
    protected $orm;

    /** @var string */
    protected $class;

    /** @var array */
    protected $options = [];

    /** @var LoaderInterface[] */
    protected $load = [];

    /** @var AbstractLoader[] */
    protected $join = [];

    /** @var AbstractLoader @invisible */
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
     * Database associated with the loader.
     *
     * @return DatabaseInterface
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->orm->getDatabase($this->class);
    }

    /**
     * {@inheritdoc}
     */
    public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface
    {
        if (!$parent instanceof AbstractLoader) {
            throw new LoaderException(sprintf(
                "Loader of type '%s' can not accept parent '%s'",
                get_class($this),
                get_class($parent)
            ));
        }

        /*
         * This scary construction simply checks if input array has keys which do not present in a
         * current set of options (i.e. default options, i.e. current options).
         */
        if (!empty($wrong = array_diff(array_keys($options), array_keys($this->options)))) {
            throw new LoaderException(sprintf(
                "Relation %s does not support option: %s",
                get_class($this),
                join(',', $wrong)
            ));
        }

        $loader = clone $this;
        $loader->parent = $parent;
        $loader->options = $options + $this->options;

        return $loader;
    }

    /**
     * Pre-load data on inner relation or relation chain. Method automatically called by Selector,
     * see load() method.
     *
     * Method support chain initiation via dot notation. Method will return already exists loader if
     * such presented.
     *
     * @see RecordSelector::load()
     *
     * @param string $relation Relation name, or chain of relations separated by.
     * @param array  $options  Loader options (to be applied to last chain element only).
     * @param bool   $join     When set to true loaders will be forced into JOIN mode.
     *
     * @return LoaderInterface Must return loader for a requested relation.
     *
     * @throws LoaderException
     */
    final public function loadRelation(
        string $relation,
        array $options,
        bool $join = false
    ): LoaderInterface {
        //Check if relation contain dot, i.e. relation chain
        if ($this->isChain($relation)) {
            return $this->loadChain($relation, $options, $join);
        }

        /*
         * Joined loaders must be isolated from normal loaders due they would not load any data
         * and will only modify SelectQuery.
         */
        if (!$join) {
            $loaders = &$this->load;
        } else {
            $loaders = &$this->join;
        }

        if ($join) {
            if (
                empty($options['method'])
                || !in_array($options['method'], [self::JOIN, self::LEFT_JOIN])
            ) {
                //Let's tell our loaded that it's method is JOIN (forced)
                $options['method'] = self::JOIN;
            }
        }

        if (isset($loaders[$relation])) {
            //Overwriting existed loader options
            return $loaders[$relation] = $loaders[$relation]->withContext($this, $options);
        }

        try {
            //Creating new loader.
            $loader = $this->orm->getFactory()->loader($this->class, $relation);
        } catch (FactoryException $e) {
            throw new LoaderException(
                "Unable to create loader: %s" . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return $loaders[$relation] = $loader->withContext($this, $options);
    }

    /**
     * {@inheritdoc}
     */
    final public function createNode(): AbstractNode
    {
        $node = $this->initNode();

        foreach ($this->load as $relation => $loader) {
            if ($loader instanceof RelationLoader && $loader->isJoined()) {
                $node->joinNode($relation, $loader->createNode());
                continue;
            }

            $node->linkNode($relation, $loader->createNode());
        }

        return $node;
    }

    /**
     * @param AbstractNode $node
     */
    public function loadData(AbstractNode $node)
    {
        // loading data thought child loaders
        foreach ($this->load as $relation => $loader) {
            $loader->loadData($node->getNode($relation));
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

    /**
     * Table alias of the loader.
     *
     * @return string
     */
    abstract protected function getAlias(): string;

    /**
     * List of columns associated with the loader.
     *
     * @return array
     */
    abstract protected function getColumns(): array;

    /**
     * Create input node for the loader.
     *
     * @return AbstractNode
     */
    abstract protected function initNode(): AbstractNode;

    /**
     * @param SelectQuery $query
     * @return SelectQuery
     */
    protected function configureQuery(SelectQuery $query): SelectQuery
    {
        foreach ($this->load as $loader) {
            if ($loader instanceof RelationLoader && $loader->isJoined()) {
                $query = $loader->configureQuery(clone $query);
            }
        }

        foreach ($this->join as $loader) {
            $query = $loader->configureQuery(clone $query);
        }

        return $query;
    }

    /**
     * Define schema option associated with the entity.
     *
     * @param int $property
     * @return mixed
     */
    protected function define(int $property)
    {
        return $this->orm->getSchema()->define($this->class, $property);
    }
}