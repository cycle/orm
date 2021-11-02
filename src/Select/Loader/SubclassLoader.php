<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\SubclassMergeNode;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select\Traits\JoinOneTableTrait;

/**
 * Load parent data.
 */
class SubclassLoader extends JoinableLoader
{
    use JoinOneTableTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     */
    protected array $options = [
        'load' => true,
        'constrain' => true,
        'method' => self::LEFT_JOIN,
        'minify' => true,
        'as' => null,
        'using' => null,
    ];

    public function __construct(ORMInterface $orm, string $role, string $target)
    {
        $schema = $orm->getSchema();

        $schemaArray = [
            Relation::INNER_KEY => $schema->define($target, SchemaInterface::PARENT_KEY)
                ?? $schema->define($role, SchemaInterface::PRIMARY_KEY),
            Relation::OUTER_KEY => $schema->define($target, SchemaInterface::PRIMARY_KEY),
        ];
        $this->options['as'] ??= $target;
        parent::__construct($orm, $role, $target, $schemaArray);
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
        return 'LEFT';
    }

    protected function initNode(): AbstractNode
    {
        return new SubclassMergeNode(
            $this->target,
            $this->columnNames(),
            (array)$this->define(SchemaInterface::PRIMARY_KEY),
            (array)$this->schema[Relation::OUTER_KEY],
            (array)$this->schema[Relation::INNER_KEY]
        );
    }

    protected function generateParentLoader(string $role): ?LoaderInterface
    {
        return null;
    }
}
