<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\ArrayNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Traits\WhereTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * Loads given entity table without any specific condition.
 */
class PivotLoader extends JoinableLoader
{
    use WhereTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'load'      => false,
        'constrain' => true,
        'method'    => self::JOIN,
        'minify'    => true,
        'as'        => null,
        'using'     => null
    ];

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->define(Schema::TABLE);
    }

    /**
     * @inheritdoc
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        // user specified WHERE conditions
        $this->setWhere(
            $query,
            $this->getAlias(),
            $this->isJoined() ? 'onWhere' : 'where',
            $this->options['where'] ?? $this->schema[Relation::THROUGH_WHERE] ?? []
        );

        return parent::configureQuery($query, $outerKeys);
    }

    /**
     * @inheritdoc
     */
    protected function initNode(): AbstractNode
    {
        $node = new ArrayNode(
            $this->columnNames(),
            $this->define(Schema::PRIMARY_KEY),
            $this->schema[Relation::THROUGH_INNER_KEY],
            $this->schema[Relation::INNER_KEY]
        );

        $typecast = $this->define(Schema::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(new Typecast($typecast, $this->getSource()->getDatabase()));
        }

        return $node;
    }
}
