<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\ParentMergeNode;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select\Traits\JoinOneTableTrait;

/**
 * Load parent data.
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

    public function __construct(ORMInterface $orm, string $role, string $target)
    {
        $schema = $orm->getSchema();

        $schemaArray = [
            Relation::INNER_KEY => $schema->define($role, SchemaInterface::PRIMARY_KEY),
            Relation::OUTER_KEY => $schema->define($role, SchemaInterface::PARENT_KEY)
                ?? $schema->define($target, SchemaInterface::PRIMARY_KEY),
        ];
        $this->options['as'] ??= $target;
        parent::__construct($orm, $role, $target, $schemaArray);
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
        // todo? throw new \RuntimeException('This method should not be called.');

        return new ParentMergeNode(
            $this->target,
            $this->columnNames(),
            (array)$this->define(SchemaInterface::PRIMARY_KEY),
            (array)$this->schema[Relation::OUTER_KEY],
            (array)$this->schema[Relation::INNER_KEY]
        );
    }
}
