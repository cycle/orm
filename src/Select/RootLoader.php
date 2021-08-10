<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Loader\ParentLoader;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Cycle\ORM\Select\Traits\ConstrainTrait;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\StatementInterface;

/**
 * Primary ORM loader. Loader wraps at top of select query in order to modify it's conditions, joins
 * and etc based on nested loaders.
 *
 * Root load does not load constrain from ORM by default.
 */
final class RootLoader extends AbstractLoader
{
    use ColumnsTrait;
    use ConstrainTrait;

    /** @var array */
    protected array $options = [
        'load'      => true,
        'constrain' => true,
    ];

    private SelectQuery $query;

    public function __construct(ORMInterface $orm, string $target)
    {
        parent::__construct($orm, $target);
        $this->query = $this->getSource()->getDatabase()->select()->from(
            sprintf('%s AS %s', $this->getSource()->getTable(), $this->getAlias())
        );
        $this->columns = $this->define(Schema::COLUMNS);

        foreach ($this->getEagerRelations() as $relation) {
            $this->loadRelation($relation, [], false, true);
        }
    }

    /**
     * Clone the underlying query.
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        parent::__clone();
    }

    public function getAlias(): string
    {
        return $this->target;
    }

    /**
     * Get primary key column identifier (aliased).
     *
     * @return string|string[]
     */
    public function getPK(): array|string
    {
        $pk = $this->define(Schema::PRIMARY_KEY);
        if (is_array($pk)) {
            $result = [];
            foreach ($pk as $key) {
                $result[] = $this->getAlias() . '.' . $this->fieldAlias($key);
            }
            return $result;
        }

        return $this->getAlias() . '.' . $this->fieldAlias($pk);
    }

    /**
     * Return base query associated with the loader.
     */
    public function getQuery(): SelectQuery
    {
        return $this->query;
    }

    /**
     * Compile query with all needed conditions, columns and etc.
     */
    public function buildQuery(): SelectQuery
    {
        return $this->configureQuery(clone $this->query);
    }

    public function loadData(AbstractNode $node, bool $includeDiscriminator = false): void
    {
        $statement = $this->buildQuery()->run();

        foreach ($statement->fetchAll(StatementInterface::FETCH_NUM) as $row) {
            $node->parseRow(0, $row);
        }

        $statement->close();

        // loading child datasets
        foreach ($this->load as $relation => $loader) {
            $loader->loadData($node->getNode($relation), $includeDiscriminator);
        }

        // Merge parent nodes
        if ($this->inherit !== null) {
            $inheritNode = $node->getParentMergeNode();
            $this->inherit->loadData($inheritNode, $includeDiscriminator);

        }
        // Merge subclass nodes
        $subclassNodes = $node->getSubclassMergeNodes();
        // todo
        foreach ($this->subclasses as $i => $loader) {
            $inheritNode = $subclassNodes[$i];
            $loader->loadData($inheritNode, $includeDiscriminator);
        }
        $node->mergeInheritanceNodes($includeDiscriminator);
    }

    public function isLoaded(): bool
    {
        // root loader is always loaded
        return true;
    }

    protected function configureQuery(SelectQuery $query): SelectQuery
    {
        return parent::configureQuery(
            $this->mountColumns($query, true, '', true)
        );
    }

    protected function initNode(): AbstractNode
    {
        $node = new RootNode($this->columnNames(), (array)$this->define(Schema::PRIMARY_KEY));

        $typecast = $this->define(Schema::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(new Typecast($typecast, $this->getSource()->getDatabase()));
        }

        return $node;
    }
}
