<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\EmbeddedNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\JoinableInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * Loads object sub-section (column subset).
 */
final class EmbeddedLoader implements JoinableInterface
{
    use ColumnsTrait;

    private ORMInterface $orm;

    private string $target;

    private ?LoaderInterface $parent = null;

    private array $options = [
        'load'   => false,
        'minify' => true,
    ];

    private array $columns = [];

    /**
     * @param ORMInterface $orm
     * @param string       $target
     */
    public function __construct(ORMInterface $orm, string $target)
    {
        $this->orm = $orm;
        $this->target = $target;

        // never duplicate primary key in data selection
        $primaryKey = $this->define(Schema::PRIMARY_KEY);
        foreach ($this->define(Schema::COLUMNS) as $internal => $external) {
            if ($internal !== $primaryKey && $external !== $primaryKey) {
                $this->columns[$internal] = $external;
            }
        }
    }

    /**
     * Destruct loader.
     */
    final public function __destruct()
    {
        $this->parent = null;
    }

    /**
     * Ensure state of every nested loader.
     */
    public function __clone()
    {
        $this->parent = null;
    }

    public function getAlias(): string
    {
        // always fallback to parent table name
        return $this->parent->getAlias();
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface
    {
        $loader = clone $this;
        $loader->parent = $parent;
        $loader->options = $options + $this->options;

        return $loader;
    }

    public function isJoined(): bool
    {
        return true;
    }

    /**
     * Indication that loader want to load data.
     */
    public function isLoaded(): bool
    {
        return $this->options['load'] ?? false;
    }

    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->isLoaded() && $this->parent->isLoaded()) {
            $this->mountColumns($query, $this->options['minify'] ?? true);
        }

        return $query;
    }

    public function createNode(): AbstractNode
    {
        $node = new EmbeddedNode(
            $this->columnNames(),
            (array)$this->orm->getSchema()->define($this->parent->getTarget(), Schema::PRIMARY_KEY)
        );

        $typecast = $this->define(Schema::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(
                new Typecast(
                    $typecast,
                    $this->orm->getSource($this->parent->getTarget())->getDatabase()
                )
            );
        }

        return $node;
    }

    public function loadData(AbstractNode $node): void
    {
        // embedded entities does not support inner loaders... for now! :)
    }

    /**
     * @return array
     */
    protected function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Define schema option associated with the entity.
     *
     * @return mixed
     */
    protected function define(int $property)
    {
        return $this->orm->getSchema()->define($this->target, $property);
    }
}
