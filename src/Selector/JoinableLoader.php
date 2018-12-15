<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector;

use Spiral\Cycle\Exception\LoaderException;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Parser\AbstractNode;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector\Traits\ColumnsTrait;
use Spiral\Cycle\Selector\Traits\ScopeTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * Provides ability to load relation data in a form of JOIN or external query.
 */
abstract class JoinableLoader extends AbstractLoader
{
    use ColumnsTrait, ScopeTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'scope'  => SourceInterface::DEFAULT_SCOPE, // scope to be used for the relation
        'method' => null,                           // load method, see AbstractLoader constants
        'minify' => true,                           // when true all loader columns will be minified (only for loading)
        'alias'  => null,                           // table alias
        'using'  => null,                           // alias used by another relation
        'where'  => null,                           // where conditions (if any)
    ];

    /** @var string */
    protected $relation;

    /** @var array */
    protected $schema;

    /**
     * @param ORMInterface $orm
     * @param string       $name
     * @param string       $target
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $target);
        $this->relation = $name;
        $this->schema = $schema;
    }

    /**
     * Relation table alias.
     *
     * @return string
     */
    public function getAlias(): string
    {
        if (!empty($this->options['using'])) {
            //We are using another relation (presumably defined by with() to load data).
            return $this->options['using'];
        }

        if (!empty($this->options['alias'])) {
            return $this->options['alias'];
        }

        throw new LoaderException("Unable to resolve loader alias");
    }

    /**
     * {@inheritdoc}
     */
    public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface
    {
        /**
         * @var AbstractLoader $parent
         * @var self           $loader
         */
        $loader = parent::withContext($parent, $options);

        if ($loader->getSource()->getDatabase() !== $parent->getSource()->getDatabase()) {
            if ($loader->isJoined()) {
                throw new LoaderException("Unable to join tables located in different databases");
            }

            // loader is not joined, let's make sure that POSTLOAD is used
            if ($this->isLoaded()) {
                $loader->options['method'] = self::POSTLOAD;
            }
        }

        //Calculate table alias
        $loader->options['alias'] = $loader->calculateAlias($parent);
        if (!empty($loader->options['scope'])) {
            if ($loader->options['scope'] instanceof ScopeInterface) {
                $loader->scope = $loader->options['scope'];
            } else {
                // we have to automatically constrain the loader query
                $loader->scope = $this->getSource()->getScope($loader->options['scope']);
            }
        }

        return $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadData(AbstractNode $node)
    {
        if ($this->isJoined() || !$this->isLoaded()) {
            // load data for all nested relations
            parent::loadData($node);

            return;
        }

        $references = $node->getReferences();
        if (empty($references)) {
            // nothing found at parent level, unable to create sub query
            return;
        }

        //Ensure all nested relations
        $statement = $this->configureQuery($this->initQuery(), $references)->run();
        $statement->setFetchMode(\PDO::FETCH_NUM);

        foreach ($statement as $row) {
            $node->parseRow(0, $row);
        }

        $statement->close();

        // load data for all nested relations
        parent::loadData($node);
    }

    /**
     * Indicated that loaded must generate JOIN statement.
     *
     * @return bool
     */
    public function isJoined(): bool
    {
        if (!empty($this->options['using'])) {
            return true;
        }

        return in_array($this->getMethod(), [self::INLOAD, self::JOIN, self::LEFT_JOIN]);
    }

    /**
     * Indication that loader want to load data.
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->getMethod() !== self::JOIN && $this->getMethod() !== self::LEFT_JOIN;
    }

    /**
     * Get load method.
     *
     * @return int
     */
    protected function getMethod(): int
    {
        return $this->options['method'];
    }

    /**
     * Configure query with conditions, joins and columns.
     *
     * @param SelectQuery $query
     * @param array       $outerKeys Set of OUTER_KEY values collected by parent loader.
     * @return SelectQuery
     */
    protected function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->isJoined()) {
            if ($this->isLoaded() && $query->getLimit() != 0) {
                throw new LoaderException("Unable to load data using join with limit on parent query");
            }

            // mounting the columns to parent query
            $this->mountColumns($query, $this->options['minify']);
        } else {
            // this is initial set of columns (remove all existed)
            $this->mountColumns($query, $this->options['minify'], '', true);
        }

        // apply the global scope
        if (!empty($this->options['using']) && !empty($this->scope)) {
            throw new LoaderException("Combination of scope and `using` source is ambiguous");
        }

        if (!empty($this->scope)) {
            $router = new QueryMapper($this->getAlias());
            $this->scope->apply($router->withQuery($query, $this->isJoined() ? 'onWhere' : 'where'));
        }

        return parent::configureQuery($query);
    }

    /**
     * Create relation specific select query.
     *
     * @return SelectQuery
     */
    protected function initQuery(): SelectQuery
    {
        return $this->getSource()->getDatabase()->select()->from($this->getJoinTable());
    }

    /**
     * Calculate table alias.
     *
     * @param AbstractLoader $parent
     * @return string
     */
    protected function calculateAlias(AbstractLoader $parent): string
    {
        if (!empty($this->options['alias'])) {
            return $this->options['alias'];
        }

        $alias = $parent->getAlias() . '_' . $this->relation;

        if ($this->isLoaded() && $this->isJoined()) {
            // to avoid collisions
            return 'l_' . $alias;
        }

        return $alias;
    }

    /**
     * Generate sql identifier using loader alias and value from relation definition. Key name to be
     * fetched from schema.
     *
     * Example:
     * $this->getKey(Relation::OUTER_KEY);
     *
     * @param mixed $key
     * @return string|null
     */
    protected function localKey($key): ?string
    {
        if (empty($this->schema[$key])) {
            return null;
        }

        return $this->getAlias() . '.' . $this->schema[$key];
    }

    /**
     * Get parent identifier based on relation configuration key.
     *
     * @param mixed $key
     * @return string
     */
    protected function parentKey($key): string
    {
        return $this->parent->getAlias() . '.' . $this->schema[$key];
    }

    /**
     * @return string
     */
    protected function getJoinMethod(): string
    {
        return $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT';
    }

    /**
     * Joined table name and alias.
     *
     * @return string
     */
    protected function getJoinTable(): string
    {
        return "{$this->define(Schema::TABLE)} AS {$this->getAlias()}";
    }

    /**
     * Relation columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return $this->define(Schema::COLUMNS);
    }
}