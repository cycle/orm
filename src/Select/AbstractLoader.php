<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\Exception\FactoryException;
use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Loader\ParentLoader;
use Cycle\ORM\Select\Loader\SubclassLoader;
use Cycle\ORM\Select\Traits\AliasTrait;
use Cycle\ORM\Select\Traits\ChainTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * ORM Loaders used to load an compile data tree based on results fetched from SQL databases,
 * loaders can communicate with SelectQuery by providing it's own set of conditions, columns
 * joins and etc. In some cases loader may create additional selector to load data using information
 * fetched from previous query.
 *
 * Attention, AbstractLoader can only work with ORM Records, you must implement LoaderInterface
 * in order to support external references (MongoDB and etc).
 *
 * Loaders can be used for both - loading and filtering of record data.
 *
 * Reference tree generation logic example:
 *   User has many Posts (relation "posts"), user primary is ID, post inner key pointing to user
 *   is USER_ID. Post loader must request User data loader to create references based on ID field
 *   values. Once Post data were parsed we can mount it under parent user using mount method:
 *
 * @see Select::load()
 * @see Select::with()
 */
abstract class AbstractLoader implements LoaderInterface
{
    use ChainTrait;
    use AliasTrait;

    // Loading methods for data loaders.
    public const INLOAD    = 1;
    public const POSTLOAD  = 2;
    public const JOIN      = 3;
    public const LEFT_JOIN = 4;

    protected ORMInterface $orm;

    protected string $target;

    protected array $options = [
        'load'      => false,
        'scope' => true,
    ];

    /** @var LoaderInterface[] */
    protected array $load = [];

    /** @var AbstractLoader[] */
    protected array $join = [];

    protected ?LoaderInterface $inherit = null;

    /** @var SubclassLoader[] */
    protected array $subclasses = [];

    protected bool $loadSubclasses = true;

    protected ?LoaderInterface $parent = null;

    /** @var array<string, array> */
    protected array $children;

    public function __construct(ORMInterface $orm, string $target)
    {
        $this->orm = $orm;
        $this->target = $target;

        $this->children = $orm->getSchema()->getInheritedRoles($target);
    }

    final public function __destruct()
    {
        unset($this->parent, $this->inherit, $this->subclasses);
        $this->load = [];
        $this->join = [];
    }

    /**
     * Ensure state of every nested loader.
     */
    public function __clone()
    {
        $this->parent = null;

        foreach ($this->load as $name => $loader) {
            $this->load[$name] = $loader->withContext($this);
        }

        foreach ($this->join as $name => $loader) {
            $this->join[$name] = $loader->withContext($this);
        }

        $this->inherit = $this->inherit?->withContext($this);

        foreach ($this->subclasses as $i => $loader) {
            $this->subclasses[$i] = $loader->withContext($this);
        }
    }

    public function setSubclassesLoading(bool $enabled): void
    {
        $this->loadSubclasses = $enabled;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Data source associated with the loader.
     */
    public function getSource(): SourceInterface
    {
        return $this->orm->getSource($this->target);
    }

    public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface
    {
        // check that given options are known
        if (!empty($wrong = array_diff(array_keys($options), array_keys($this->options)))) {
            throw new LoaderException(
                sprintf(
                    'Relation %s does not support option: %s',
                    get_class($this),
                    implode(',', $wrong)
                )
            );
        }

        $loader = clone $this;
        $loader->parent = $parent;
        $loader->options = $options + $this->options;

        return $loader;
    }

    /**
     * Load the relation.
     *
     * @param string|LoaderInterface $relation Relation name, or chain of relations separated by. If you need to set
     * inheritance then pass LoaderInterface object
     * @param array  $options  Loader options (to be applied to last chain element only).
     * @param bool   $join     When set to true loaders will be forced into JOIN mode.
     * @param bool   $load     Load relation data.
     * @return LoaderInterface Must return loader for a requested relation.
     *
     * @throws LoaderException
     */
    public function loadRelation(
        string|LoaderInterface $relation,
        array $options,
        bool $join = false,
        bool $load = false
    ): LoaderInterface {
        if ($relation instanceof ParentLoader) {
            return $this->inherit = $relation->withContext($this);
        }
        if ($relation instanceof SubclassLoader) {
            $loader = $relation->withContext($this);
            $this->subclasses[] = $loader;
            return $loader;
        }
        $relation = $this->resolvePath($relation);
        if (!empty($options['as'])) {
            $this->registerPath($options['as'], $relation);
        }

        //Check if relation contain dot, i.e. relation chain
        if ($this->isChain($relation)) {
            return $this->loadChain($relation, $options, $join, $load);
        }

        /*
         * Joined loaders must be isolated from normal loaders due they would not load any data
         * and will only modify SelectQuery.
         */
        if (!$join || $load) {
            $loaders = &$this->load;
        } else {
            $loaders = &$this->join;
        }

        if ($load) {
            $options['load'] ??= true;
        }

        if (isset($loaders[$relation])) {
            // overwrite existing loader options
            return $loaders[$relation] = $loaders[$relation]->withContext($this, $options);
        }

        if ($join) {
            if (empty($options['method']) || !in_array($options['method'], [self::JOIN, self::LEFT_JOIN], true)) {
                // let's tell our loaded that it's method is JOIN (forced)
                $options['method'] = self::JOIN;
            }
        }

        try {
            //Creating new loader.
            $loader = $this->orm->getFactory()->loader(
                $this->orm,
                $this->orm->getSchema(),
                $this->target,
                $relation
            );
        } catch (SchemaException | FactoryException $e) {
            if ($this->inherit instanceof self) {
                return $this->inherit->loadRelation($relation, $options, $join, $load);
            }
            // foreach ($this->subclasses as $loader) {
            //     if ($loader instanceof ParentLoader) {
            //         return $loader->loadRelation($relation, $options, $join, $load);
            //     }
            // }
            throw new LoaderException(
                sprintf('Unable to create loader: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        return $loaders[$relation] = $loader->withContext($this, $options);
    }

    public function createNode(): AbstractNode
    {
        $node = $this->initNode();

        if ($this->inherit !== null) {
            $node->joinNode(null, $this->inherit->createNode());
        }

        foreach ($this->load as $relation => $loader) {
            if ($loader instanceof JoinableInterface && $loader->isJoined()) {
                $node->joinNode($relation, $loader->createNode());
                continue;
            }

            $node->linkNode($relation, $loader->createNode());
        }

        if ($this->loadSubclasses) {
            foreach ($this->subclasses as $loader) {
                $node->joinNode(null, $loader->createNode());
            }
        }

        return $node;
    }

    public function loadData(AbstractNode $node, bool $includeRole = false): void
    {
        $this->loadChild($node, $includeRole);
    }

    /**
     * Indicates that loader loads data.
     */
    abstract public function isLoaded(): bool;

    protected function loadChild(AbstractNode $node, bool $includeRole = false): void
    {
        foreach ($this->load as $relation => $loader) {
            $loader->loadData($node->getNode($relation), $includeRole);
        }
        $this->loadIerarchy($node, $includeRole);
    }

    protected function loadIerarchy(AbstractNode $node, bool $includeRole = false): void
    {
        if ($this->inherit === null && !$this->loadSubclasses) {
            return;
        }

        // Merge parent nodes
        if ($this->inherit !== null) {
            $inheritNode = $node->getParentMergeNode();
            $this->inherit->loadData($inheritNode, $includeRole);
        }

        // Merge subclass nodes
        if ($this->loadSubclasses) {
            $subclassNodes = $node->getSubclassMergeNodes();
            foreach ($this->subclasses as $i => $loader) {
                $inheritNode = $subclassNodes[$i];
                $loader->loadData($inheritNode, $includeRole);
            }
        }

        $node->mergeInheritanceNodes($includeRole);
    }

    /**
     * Create input node for the loader.
     */
    abstract protected function initNode(): AbstractNode;

    protected function configureQuery(SelectQuery $query): SelectQuery
    {
        $query = $this->applyScope($query);

        if ($this->inherit !== null) {
            $query = $this->inherit->configureQuery($query);
        }

        foreach ($this->join as $loader) {
            $query = $loader->configureQuery($query);
        }

        foreach ($this->load as $loader) {
            if ($loader instanceof JoinableInterface && $loader->isJoined()) {
                $query = $loader->configureQuery($query);
            }
        }

        if ($this->loadSubclasses) {
            foreach ($this->subclasses as $loader) {
                $query = $loader->configureQuery($query);
            }
        }

        return $query;
    }

    abstract protected function applyScope(SelectQuery $query): SelectQuery;

    /**
     * Define schema option associated with the entity.
     *
     * @return mixed
     */
    protected function define(int $property)
    {
        return $this->orm->getSchema()->define($this->target, $property);
    }

    /**
     * Returns list of relations to be automatically joined with parent object.
     */
    protected function getEagerLoaders(string $role = null): \Generator
    {
        $role ??= $this->target;
        $parentLoader = $this->generateParentLoader($role);
        if ($parentLoader !== null) {
            yield $this->generateParentLoader($role);
        }
        yield from $this->generateSublassLoaders();
        yield from $this->generateEagerRelationLoaders($role);
    }

    protected function generateParentLoader(string $role): ?LoaderInterface
    {
        $schema = $this->orm->getSchema();
        $parent = $schema->define($role, SchemaInterface::PARENT);
        $factory = $this->orm->getFactory();
        return $parent === null ? null : $factory->loader($this->orm, $schema, $role, FactoryInterface::PARENT_LOADER);
    }

    protected function generateSublassLoaders(): iterable
    {
        $schema = $this->orm->getSchema();
        $factory = $this->orm->getFactory();
        if ($this->children !== []) {
            foreach ($this->children as $subRole => $children) {
                yield $factory->loader($this->orm, $schema, $subRole, FactoryInterface::CHILD_LOADER);
            }
        }
    }

    protected function generateEagerRelationLoaders(string $target): \Generator
    {
        $relations = $this->orm->getSchema()->define($target, SchemaInterface::RELATIONS) ?? [];
        foreach ($relations as $relation => $schema) {
            if (($schema[Relation::LOAD] ?? null) === Relation::LOAD_EAGER) {
                yield $relation;
            }
        }
    }
}
