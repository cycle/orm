<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\EmbeddedNode;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\JoinableInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Cycle\Database\Query\SelectQuery;

/**
 * Loads object sub-section (column subset).
 *
 * @internal
 */
final class EmbeddedLoader implements JoinableInterface
{
    use ColumnsTrait;

    private ?LoaderInterface $parent = null;
    private array $options = [
        'load' => false,
        'minify' => true,
    ];

    public function __construct(
        private SchemaInterface $ormSchema,
        private string $target,
    ) {
        // never duplicate primary key in data selection
        $primaryKey = (array) $this->define(SchemaInterface::PRIMARY_KEY);
        foreach ($this->normalizeColumns($this->define(SchemaInterface::COLUMNS)) as $internal => $external) {
            if (!\in_array($internal, $primaryKey, true)) {
                $this->columns[$internal] = $external;
            }
        }
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
        return new EmbeddedNode(
            $this->columnNames(),
            (array) $this->ormSchema->define($this->parent->getTarget(), SchemaInterface::PRIMARY_KEY),
        );
    }

    public function loadData(AbstractNode $node, bool $includeRole = false): void
    {
        // embedded entities does not support inner loaders... for now! :)
    }

    public function setSubclassesLoading(bool $enabled): void {}

    public function isHierarchical(): bool
    {
        // Embedded can't be hierarchical
        return false;
    }

    /**
     * Ensure state of every nested loader.
     */
    public function __clone()
    {
        $this->parent = null;
    }

    /**
     * Destruct loader.
     */
    public function __destruct()
    {
        unset($this->parent);
    }

    /**
     * Define schema option associated with the entity.
     *
     * @return mixed
     */
    protected function define(int $property)
    {
        return $this->ormSchema->define($this->target, $property);
    }
}
