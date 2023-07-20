<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\Database\Query\SelectQuery;
use Cycle\Database\StatementInterface;
use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Loader\SubQueryLoader;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Cycle\ORM\Select\Traits\ScopeTrait;

/**
 * Provides ability to load relation data in a form of JOIN or external query.
 *
 * @internal
 */
abstract class JoinableLoader extends AbstractLoader implements JoinableInterface
{
    use ColumnsTrait;
    use ScopeTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     */
    protected array $options = [
        // load relation data
        'load' => false,

        // true or instance to enable, false or null to disable
        'scope' => true,

        // scope to be used for the relation
        'method' => null,

        // load method, see AbstractLoader constants
        'minify' => true,

        // when true all loader columns will be minified (only for loading)
        'as' => null,

        // table alias
        'using' => null,

        // alias used by another relation
        'where' => null,

        // where conditions (if any)
    ];

    /**
     * Eager relations and inheritance hierarchies has been loaded
     */
    private bool $eagerLoaded = false;

    public function __construct(
        SchemaInterface $ormSchema,
        SourceProviderInterface $sourceProvider,
        FactoryInterface $factory,
        protected string $name,
        string $target,
        protected array $schema
    ) {
        parent::__construct($ormSchema, $sourceProvider, $factory, $target);
        $this->columns = $this->normalizeColumns($this->define(SchemaInterface::COLUMNS));
    }

    /**
     * Relation table alias.
     */
    public function getAlias(): string
    {
        if ($this->options['using'] !== null) {
            //We are using another relation (presumably defined by with() to load data).
            return $this->options['using'];
        }

        if ($this->options['as'] !== null) {
            return $this->options['as'];
        }

        throw new LoaderException('Unable to resolve loader alias.');
    }

    public function withContext(LoaderInterface $parent, array $options = []): static
    {
        /**
         * @var AbstractLoader $parent
         * @var self $loader
         */
        $loader = parent::withContext($parent, $options);

        if ($loader->source->getDatabase() !== $parent->source->getDatabase()) {
            if ($loader->isJoined()) {
                throw new LoaderException('Unable to join tables located in different databases');
            }

            // loader is not joined, let's make sure that POSTLOAD is used
            if ($this->isLoaded()) {
                $loader->options['method'] = self::POSTLOAD;
            }
        }

        //Calculate table alias
        $loader->options['as'] = $loader->calculateAlias($parent);

        if (\array_key_exists('scope', $options)) {
            if ($loader->options['scope'] instanceof ScopeInterface) {
                $loader->setScope($loader->options['scope']);
            } elseif (\is_string($loader->options['scope'])) {
                $loader->setScope($this->factory->make($loader->options['scope']));
            }
        } else {
            $loader->setScope($this->source->getScope());
        }

        if (!$loader->eagerLoaded && $loader->isLoaded()) {
            $loader->eagerLoaded = true;
            $loader->inherit = null;
            $loader->subclasses = [];
            foreach ($loader->getEagerLoaders() as $relation) {
                $loader->loadRelation($relation, [], false, true);
            }
        }

        return $loader;
    }

    public function loadData(AbstractNode $node, bool $includeRole = false): void
    {
        if ($this->isJoined() || !$this->isLoaded()) {
            // load data for all nested relations
            parent::loadData($node, $includeRole);

            return;
        }

        // Get list of reference key values aggregated by parent.
        $references = $node->getReferenceValues();
        if ($references === []) {
            // nothing found at parent level, unable to create sub query
            return;
        }

        //Ensure all nested relations
        $statement = $this->configureQuery($this->initQuery(), $references)->run();

        foreach ($statement->fetchAll(StatementInterface::FETCH_NUM) as $row) {
            try {
                $node->parseRow(0, $row);
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        $statement->close();

        // load data for all nested relations
        parent::loadData($node, $includeRole);
    }

    /**
     * Indicated that loaded must generate JOIN statement.
     */
    public function isJoined(): bool
    {
        if (!empty($this->options['using'])) {
            return true;
        }

        return in_array($this->getMethod(), [self::INLOAD, self::JOIN, self::LEFT_JOIN], true);
    }

    /**
     * Indicated that loaded must generate JOIN statement.
     */
    public function isSubQueried(): bool
    {
        return $this->getMethod() === self::SUBQUERY;
    }

    /**
     * Indication that loader want to load data.
     */
    public function isLoaded(): bool
    {
        return $this->options['load'] || in_array($this->getMethod(), [self::INLOAD, self::POSTLOAD], true);
    }

    protected function configureSubQuery(SelectQuery $query): SelectQuery
    {
        if (!$this->isJoined()) {
            return $this->configureQuery($query);
        }

        $loader = new SubQueryLoader($this->ormSchema, $this->sourceProvider, $this->factory, $this, $this->options);
        return $loader->configureQuery($query);
    }

    /**
     * Configure query with conditions, joins and columns.
     *
     * @param array $outerKeys Set of OUTER_KEY values collected by parent loader.
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->isLoaded()) {
            if ($this->isJoined() || $this->isSubQueried()) {
                // mounting the columns to parent query
                $this->mountColumns($query, $this->options['minify']);
            } else {
                // this is initial set of columns (remove all existed)
                $this->mountColumns($query, $this->options['minify'], '', true);
            }

            if ($this->options['load'] instanceof ScopeInterface) {
                $this->options['load']->apply($this->makeQueryBuilder($query));
            }

            if (\is_callable($this->options['load'], true)) {
                ($this->options['load'])($this->makeQueryBuilder($query));
            }
        }

        return parent::configureQuery($query);
    }

    protected function applyScope(SelectQuery $query): SelectQuery
    {
        $this->scope?->apply($this->makeQueryBuilder($query));

        return $query;
    }

    /**
     * Get load method.
     */
    protected function getMethod(): int
    {
        return $this->options['method'];
    }

    /**
     * Create relation specific select query.
     */
    protected function initQuery(): SelectQuery
    {
        return $this->source->getDatabase()->select()->from($this->getJoinTable());
    }

    /**
     * Calculate table alias.
     */
    protected function calculateAlias(AbstractLoader $parent): string
    {
        if (!empty($this->options['as'])) {
            return $this->options['as'];
        }

        $alias = $parent->getAlias() . '_' . $this->name;

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
     */
    protected function localKey(string|int $key): ?string
    {
        if (empty($this->schema[$key])) {
            return null;
        }

        return $this->getAlias() . '.' . $this->fieldAlias($this->schema[$key]);
    }

    /**
     * Get parent identifier based on relation configuration key.
     */
    protected function parentKey(string|int $key): string
    {
        return $this->parent->getAlias() . '.' . $this->parent->fieldAlias($this->schema[$key]);
    }

    protected function getJoinMethod(): string
    {
        return $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT';
    }

    /**
     * Joined table name and alias.
     */
    protected function getJoinTable(): string
    {
        return "{$this->define(SchemaInterface::TABLE)} AS {$this->getAlias()}";
    }

    private function makeQueryBuilder(SelectQuery $query): QueryBuilder
    {
        $builder = new QueryBuilder($query, $this);
        if ($this->isJoined()) {
            return $builder->withForward('onWhere');
        }

        return $builder;
    }
}
