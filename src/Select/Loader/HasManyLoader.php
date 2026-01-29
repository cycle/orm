<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\ArrayNode;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Traits\JoinOneTableTrait;
use Cycle\ORM\Select\Traits\OrderByTrait;
use Cycle\ORM\Select\Traits\WhereTrait;

/**
 * @internal
 */
class HasManyLoader extends JoinableLoader
{
    use JoinOneTableTrait;
    use OrderByTrait;
    use WhereTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     */
    protected array $options = [
        'load' => false,
        'scope' => true,
        'method' => self::POSTLOAD,
        'minify' => true,
        'as' => null,
        'using' => null,
        'where' => null,
        'orderBy' => null,
    ];

    public function __construct(
        SchemaInterface $ormSchema,
        SourceProviderInterface $sourceProvider,
        FactoryInterface $factory,
        string $name,
        string $target,
        array $schema,
    ) {
        parent::__construct($ormSchema, $sourceProvider, $factory, $name, $target, $schema);
        $this->options['where'] = $schema[Relation::WHERE] ?? [];
        $this->options['orderBy'] = $schema[Relation::ORDER_BY] ?? [];
    }

    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->isLoaded() && $this->isJoined() && (int) $query->getLimit() !== 0) {
            throw new LoaderException('Unable to load data using join with limit on parent query');
        }

        if ($this->options['using'] !== null) {
            // use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        $this->configureParentQuery($query, $outerKeys);

        // user specified WHERE conditions
        $this->setWhere(
            $query,
            $this->isJoined() ? 'onWhere' : 'where',
            $this->options['where'] ?? $this->schema[Relation::WHERE] ?? [],
        );

        // user specified ORDER_BY rules
        $this->setOrderBy(
            $query,
            $this->getAlias(),
            $this->options['orderBy'] ?? $this->schema[Relation::ORDER_BY] ?? [],
        );

        return parent::configureQuery($query);
    }

    protected function initNode(): AbstractNode
    {
        return new ArrayNode(
            $this->columnNames(),
            (array) $this->define(SchemaInterface::PRIMARY_KEY),
            (array) $this->schema[Relation::OUTER_KEY],
            (array) $this->schema[Relation::INNER_KEY],
        );
    }
}
