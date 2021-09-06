<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Cycle\ORM\Select\Traits\ScopeTrait;
use Cycle\Database\Query\SelectQuery;

/**
 * Primary ORM loader. Loader wraps at top of select query in order to modify it's conditions, joins
 * and etc based on nested loaders.
 *
 * Root load does not load constrain from ORM by default.
 *
 * @method RootNode createNode()
 */
final class SubQueryLoader extends JoinableLoader
{
    use ColumnsTrait;
    use ScopeTrait;

    /** @var array */
    protected array $options = [
        'load' => true,
        'scope' => true,
        'using' => null,
    ];

    private JoinableLoader $loader;

    public function __construct(ORMInterface $orm, JoinableLoader $loader, array $options)
    {
        parent::__construct($orm, $loader->name, $loader->getTarget(), $loader->schema);

        $this->loader = $loader->withContext($this, [
            'method' => self::SUBQUERY,
        ]);
        $this->options = $options;
        $this->options['as'] = 'sq_' . $options['as'];
        $this->columns = $loader->columns;
        $this->parent = $loader->parent;
    }

    /**
     * Get primary key column identifier (aliased).
     *
     * @return string|string[]
     */
    public function getPK(): array|string
    {
        $pk = $this->define(Schema::PRIMARY_KEY);
        if (\is_array($pk)) {
            $result = [];
            foreach ($pk as $key) {
                $result[] = $this->getAlias() . '.' . $this->fieldAlias($key);
            }
            return $result;
        }

        return $this->getAlias() . '.' . $this->fieldAlias($pk);
    }

    public function isLoaded(): bool
    {
        // root loader is always loaded
        return true;
    }

    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        $alias = $this->options['as'];
        $lAlias = $this->loader->getAlias();
        $queryColumns = $query->getColumns();

        $body = $this->loader->getSource()->getDatabase()->select()->from(
            sprintf('%s AS %s', $this->loader->getSource()->getTable(), $lAlias)
        )->columns($queryColumns);
        $body = $this->loader->configureQuery($body);
        $bodyColumns = array_slice($body->getColumns(), count($queryColumns));
        $body = $body->columns($bodyColumns);

        $aliases = [];
        // Move columns to parent query
        foreach ($bodyColumns as $column) {
            preg_match('/^"?([^\\s"]+)"?\\."?([^\\s"]+)"? AS "?([^\\s"]+)"?$/i', $column, $matches);
            [, $table, $column, $as] = $matches;
            $queryColumns[] = "{$alias}.{$as} AS {$as}";
            if ($table === $lAlias) {
                $aliases[$column] = $as;
            }
        }

        // $query = $query->columns(array_merge($query->getColumns(), [$this->options['as'] . '.*']));
        // todo test this chose using two hierarchical joins
        $query = $query->columns($queryColumns);

        $parentKeys = (array)$this->schema[Relation::INNER_KEY];
        $parentPrefix = $this->parent->getAlias() . '.';
        $on = [];
        foreach ((array)$this->schema[Relation::OUTER_KEY] as $i => $key) {
            $field = $alias . '.' . $aliases[$this->fieldAlias($key)];
            $on[$field] = $parentPrefix . $this->parent->fieldAlias($parentKeys[$i]);
        }
        return $query->join($this->getJoinMethod(), $body, $alias)->on($on);
    }

    protected function initNode(): RootNode
    {
        $node = new RootNode($this->columnNames(), (array)$this->define(Schema::PRIMARY_KEY));

        $typecast = $this->define(Schema::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(new Typecast($typecast, $this->getSource()->getDatabase()));
        }

        return $node;
    }
}
