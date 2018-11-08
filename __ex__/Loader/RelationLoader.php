<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Loader;

use Spiral\Database\Builders\SelectQuery;
use Spiral\Treap\Loader\Traits\ColumnsTrait;
use Spiral\ORM\Entities\Nodes\AbstractNode;
use Spiral\ORM\Exceptions\LoaderException;
use Spiral\ORM\LoaderInterface;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Record;

/**
 * Provides ability to load relation data in a form of JOIN or external query.
 */
abstract class RelationLoader extends AbstractLoader
{
    use ColumnsTrait;

    /**
     * Used to create unique set of aliases for loaded relations.
     *
     * @var int
     */
    private static $countLevels = 0;

    /**
     * Name of relation loader associated with.
     *
     * @var string
     */
    protected $relation;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        //Load method, see QueryLoader constants
        'method' => null,

        //When true all loader columns will be minified (only for loading)
        'minify' => true,

        //Table alias
        'alias'  => null,

        //Alias used by another relation
        'using'  => null,

        //Where conditions (if any)
        'where'  => null,
    ];

    /**
     * @param string       $class
     * @param string       $relation
     * @param array        $schema
     * @param ORMInterface $orm
     */
    public function __construct(string $class, string $relation, array $schema, ORMInterface $orm)
    {
        parent::__construct($class, $schema, $orm);

        //We need related model primary keys in order to ensure that
        $this->schema[Record::SH_PRIMARY_KEY] = $orm->define($class, ORMInterface::R_PRIMARY_KEY);
        $this->relation = $relation;
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

        if ($loader->getDatabase() != $parent->getDatabase()) {
            if ($loader->isJoined()) {
                throw new LoaderException('Unable to join tables located in different databases');
            }

            //Loader is not joined, let's make sure that POSTLOAD is used
            if ($this->isLoaded()) {
                $loader->options['method'] = self::POSTLOAD;
            }
        }

        //Calculate table alias
        $loader->ensureAlias($parent);

        return $loader;
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
     * {@inheritdoc}
     */
    public function loadData(AbstractNode $node)
    {
        if ($this->isJoined() || !$this->isLoaded()) {
            //Loading data for all nested relations
            parent::loadData($node);

            return;
        }

        $references = $node->getReferences();
        if (empty($references)) {
            //Nothing found at parent level, unable to create sub query
            return;
        }

        //Ensure all nested relations
        $statement = $this->configureQuery($this->createQuery(), $references)->run();
        $statement->setFetchMode(\PDO::FETCH_NUM);

        foreach ($statement as $row) {
            $node->parseRow(0, $row);
        }

        $statement->close();

        //Loading data for all nested relations
        parent::loadData($node);
    }

    /**
     * Configure query with conditions, joins and columns.
     *
     * @param SelectQuery $query
     * @param array       $outerKeys Set of OUTER_KEY values collected by parent loader.
     *
     * @return SelectQuery
     */
    protected function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->isJoined()) {
            //Mounting columns
            $this->mountColumns($query, $this->options['minify']);
        } else {
            //This is initial set of columns (remove all existed)
            $this->mountColumns($query, $this->options['minify'], '', true);
        }

        return parent::configureQuery($query);
    }

    /**
     * Relation table alias.
     *
     * @return string
     */
    protected function getAlias(): string
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
     * Relation columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return $this->schema[Record::RELATION_COLUMNS];
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
     * Generate sql identifier using loader alias and value from relation definition. Key name to be
     * fetched from schema.
     *
     * Example:
     * $this->getKey(Record::OUTER_KEY);
     *
     * @param string $key
     *
     * @return string|null
     */
    protected function localKey($key)
    {
        if (empty($this->schema[$key])) {
            return null;
        }

        return $this->getAlias() . '.' . $this->schema[$key];
    }

    /**
     * Get parent identifier based on relation configuration key.
     *
     * @param $key
     *
     * @return string
     */
    protected function parentKey($key): string
    {
        return $this->parent->getAlias() . '.' . $this->schema[$key];
    }

    /**
     * Ensure table alias.
     *
     * @param AbstractLoader $parent
     */
    protected function ensureAlias(AbstractLoader $parent)
    {
        //Let's calculate loader alias
        if (empty($this->options['alias'])) {
            if ($this->isLoaded() && $this->isJoined()) {
                //Let's create unique alias, we are able to do that for relations just loaded
                $this->options['alias'] = 'd' . decoct(++self::$countLevels);
            } else {
                //Let's use parent alias to continue chain
                $this->options['alias'] = $parent->getAlias() . '_' . $this->relation;
            }
        }
    }

    /**
     * Create relation specific select query.
     *
     * @return SelectQuery
     */
    protected function createQuery(): SelectQuery
    {
        return $this->orm->table($this->class)->select()->from(
            "{$this->getTable()} AS {$this->getAlias()}"
        );
    }
}