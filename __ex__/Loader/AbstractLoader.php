<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Loader;

use Spiral\Database\Builders\SelectQuery;
use Spiral\ORM\Entities\Nodes\AbstractNode;
use Spiral\ORM\Entities\RecordSelector;
use Spiral\ORM\Exceptions\LoaderException;
use Spiral\ORM\Exceptions\ORMException;
use Spiral\ORM\LoaderInterface;
use Spiral\ORM\ORMInterface;
use Spiral\Treap\Loader\Traits\ColumnsTrait;

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
 * User has many Posts (relation "posts"), user primary is ID, post inner key pointing to user
 * is USER_ID. Post loader must request User data loader to create references based on ID field
 * values. Once Post data were parsed we can mount it under parent user using mount method:
 *
 * @see Selector::load()
 * @see Selector::with()
 */
abstract class AbstractLoader
{
    use ColumnsTrait;



    /**
     * @var string
     */
    protected $class;


    /**
     * Relation schema.
     *
     * @var array
     */
    protected $schema = [];


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
            $loaders = &$this->loaders;
        } else {
            $loaders = &$this->joiners;
        }

        if ($join) {
            if (empty($options['method']) || !in_array($options['method'], [self::JOIN, self::LEFT_JOIN])) {
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
            $loader = $this->orm->makeLoader($this->class, $relation);
        } catch (ORMException $e) {
            throw new LoaderException("Unable to create loader", $e->getCode(), $e);
        }

        //Configuring loader scope
        return $loaders[$relation] = $loader->withContext($this, $options);
    }

    /**
     * {@inheritdoc}
     */
    final public function createNode(): AbstractNode
    {
        $node = $this->initNode();

        //Working with nested relation loaders
        foreach ($this->loaders as $relation => $loader) {
            $node->registerNode($relation, $loader->createNode());
        }

        return $node;
    }



    /**
     * @param SelectQuery $query
     *
     * @return SelectQuery
     */
    protected function configureQuery(SelectQuery $query): SelectQuery
    {
        foreach ($this->loaders as $loader) {
            if ($loader instanceof RelationLoader && $loader->isJoined()) {
                $query = $loader->configureQuery(clone $query);
            }
        }

        foreach ($this->joiners as $loader) {
            $query = $loader->configureQuery(clone $query);
        }

        return $query;
    }

    /**
     * Get database name associated with relation
     *
     * @return string
     */
    protected function getDatabase(): string
    {
        return $this->orm->define($this->class, ORMInterface::R_DATABASE);
    }

    /**
     * Get table name associated with relation
     *
     * @return string
     */
    protected function getTable(): string
    {
        return $this->orm->define($this->class, ORMInterface::R_TABLE);
    }

    /**
     * @return AbstractNode
     */
    abstract protected function initNode(): AbstractNode;

    /**
     * Joined table alias.
     *
     * @return string
     */
    abstract protected function getAlias(): string;

    /**
     * list of columns to be loaded.
     *
     * @return array
     */
    abstract protected function getColumns(): array;

    /**
     * Check if given relation is actually chain of relations.
     *
     * @param string $relation
     *
     * @return bool
     */
    private function isChain(string $relation): bool
    {
        return strpos($relation, '.') !== false;
    }

    /**
     * @see loadRelation()
     * @see joinRelation()
     *
     * @param string $chain
     * @param array  $options Final loader options.
     * @param bool   $join    See loadRelation().
     *
     * @return LoaderInterface
     *
     * @throws LoaderException When one of chain elements is not actually chainable (let's say ODM
     *                         loader).
     */
    private function loadChain(string $chain, array $options, bool $join): LoaderInterface
    {
        $position = strpos($chain, '.');

        //Chain of relations provided (relation.nestedRelation)
        $child = $this->loadRelation(
            substr($chain, 0, $position),
            [],
            $join
        );

        if (!$child instanceof AbstractLoader) {
            throw new LoaderException(sprintf(
                "Loader '%s' does not support chain relation loading",
                get_class($child)
            ));
        }

        //Loading nested relation thought chain (chainOptions prior to user options)
        return $child->loadRelation(
            substr($chain, $position + 1),
            $options,
            $join
        );
    }
}
