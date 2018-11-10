<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Loader;

use Spiral\Database\Builders\SelectQuery;
use Spiral\Database\Injections\Parameter;
use Spiral\ORM\Entities\Nodes\AbstractNode;
use Spiral\ORM\Entities\Nodes\SingularNode;
use Spiral\ORM\Record;

/**
 * Responsible for loading data related to parent record in belongs to relation. Loading logic is
 * identical to HasOneLoader however preferred loading methods is POSTLOAD.
 */
class BelongsToLoader extends RelationLoader
{
    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'method' => self::POSTLOAD,
        'minify' => true,
        'alias'  => null,
        'using'  => null,
        'where'  => null,
    ];

    /**
     * {@inheritdoc}
     */
    protected function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if (!empty($this->options['using'])) {
            //Use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        if ($this->isJoined()) {
            $query->join(
                $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT',
                "{$this->getTable()} AS {$this->getAlias()}")
                ->on(
                    $this->localKey(Record::OUTER_KEY),
                    $this->parentKey(Record::INNER_KEY)
                );
        } else {
            //This relation is loaded using external query
            $query->where(
                $this->localKey(Record::OUTER_KEY),
                'IN',
                new Parameter($outerKeys)
            );
        }

        return parent::configureQuery($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function initNode(): AbstractNode
    {
        $node = new SingularNode(
            $this->schema[Record::RELATION_COLUMNS],
            $this->schema[Record::OUTER_KEY],
            $this->schema[Record::INNER_KEY],
            $this->schema[Record::SH_PRIMARY_KEY]
        );

        return $node->asJoined($this->isJoined());
    }
}