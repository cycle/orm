<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\StaticNode;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Cycle\ORM\Select\Traits\ScopeTrait;

/**
 * @internal
 */
final class UpdateLoader extends AbstractLoader
{
    use ColumnsTrait;
    use ScopeTrait;

    protected array $options = [
        'load' => true,
        'scope' => true,
    ];

    /**
     * @param non-empty-string $target Entity role or alias.
     */
    public function __construct(
        SchemaInterface $ormSchema,
        SourceProviderInterface $sourceProvider,
        FactoryInterface $factory,
        string $target,
    ) {
        parent::__construct($ormSchema, $sourceProvider, $factory, $target);
        $this->columns = $this->normalizeColumns($this->define(SchemaInterface::COLUMNS));
    }

    public function getAlias(): string
    {
        return $this->target;
    }

    public function loadData(AbstractNode $node, bool $includeRole = false): void
    {
        // loading child datasets
        foreach ($this->load as $relation => $loader) {
            $loader->loadData($node->getNode($relation), $includeRole);
        }

        // $this->loadHierarchy($node, $includeRole);
    }

    public function isLoaded(): bool
    {
        return true;
    }

    protected function initNode(): StaticNode
    {
        return new StaticNode($this->columnNames(), (array) $this->define(SchemaInterface::PRIMARY_KEY));
    }
}
