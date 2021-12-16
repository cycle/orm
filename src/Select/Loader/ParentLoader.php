<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\ParentMergeNode;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Traits\JoinOneTableTrait;

/**
 * Load parent data.
 *
 * @internal
 */
class ParentLoader extends JoinableLoader
{
    use JoinOneTableTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     */
    protected array $options = [
        'load' => true,
        'constrain' => true,
        'method' => self::INLOAD,
        'minify' => true,
        'as' => null,
        'using' => null,
    ];

    public function __construct(
        SchemaInterface $ormSchema,
        SourceProviderInterface $sourceProvider,
        FactoryInterface $factory,
        string $role,
        string $target
    ) {
        $schemaArray = [
            Relation::INNER_KEY => $ormSchema->define($role, SchemaInterface::PRIMARY_KEY),
            Relation::OUTER_KEY => $ormSchema->define($role, SchemaInterface::PARENT_KEY)
                ?? $ormSchema->define($target, SchemaInterface::PRIMARY_KEY),
        ];
        $this->options['as'] ??= $target;
        parent::__construct($ormSchema, $sourceProvider, $factory, $role, $target, $schemaArray);
    }

    protected function generateSublassLoaders(): iterable
    {
        return [];
    }

    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->options['using'] !== null) {
            // use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        $this->configureParentQuery($query, $outerKeys);

        return parent::configureQuery($query);
    }

    protected function getJoinMethod(): string
    {
        return 'INNER';
    }

    protected function initNode(): AbstractNode
    {
        return new ParentMergeNode(
            $this->target,
            $this->columnNames(),
            (array)$this->define(SchemaInterface::PRIMARY_KEY),
            (array)$this->schema[Relation::OUTER_KEY],
            (array)$this->schema[Relation::INNER_KEY]
        );
    }
}
