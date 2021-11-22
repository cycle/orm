<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\SingularNode;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Traits\JoinOneTableTrait;
use Cycle\ORM\Select\Traits\WhereTrait;
use Cycle\Database\Query\SelectQuery;

/**
 * Dedicated to load HAS_ONE relations, by default loader will prefer to join data into query.
 * Loader support MORPH_KEY.
 *
 * Please note that OUTER and INNER keys defined from perspective of parent (reversed for our
 * purposes).
 *
 * @internal
 */
class HasOneLoader extends JoinableLoader
{
    use JoinOneTableTrait;
    use WhereTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     */
    protected array $options = [
        'load' => false,
        'scope' => true,
        'method' => self::INLOAD,
        'minify' => true,
        'as' => null,
        'using' => null,
        'where' => null,
    ];

    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->options['using'] !== null) {
            // use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        $this->configureParentQuery($query, $outerKeys);

        // user specified WHERE conditions
        $this->setWhere(
            $query,
            $this->isJoined() ? 'onWhere' : 'where',
            $this->options['where'] ?? $this->schema[Relation::WHERE] ?? []
        );

        return parent::configureQuery($query);
    }

    protected function initNode(): AbstractNode
    {
        return new SingularNode(
            $this->columnNames(),
            (array)$this->define(SchemaInterface::PRIMARY_KEY),
            (array)$this->schema[Relation::OUTER_KEY],
            (array)$this->schema[Relation::INNER_KEY]
        );
    }
}
