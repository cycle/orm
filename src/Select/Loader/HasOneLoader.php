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
use Cycle\ORM\Parser\SingularNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Traits\WhereTrait;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\SelectQuery;

/**
 * Dedicated to load HAS_ONE relations, by default loader will prefer to join data into query.
 * Loader support MORPH_KEY.
 *
 * Please note that OUTER and INNER keys defined from perspective of parent (reversed for our
 * purposes).
 */
class HasOneLoader extends JoinableLoader
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
        'method'    => self::INLOAD,
        'minify'    => true,
        'as'        => null,
        'using'     => null,
        'where'     => null
    ];

    /**
     * {@inheritdoc}
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->options['using'] !== null) {
            // use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        $localKey = $this->localKey(Relation::OUTER_KEY);

        if ($this->isJoined()) {
            $query->join(
                $this->getJoinMethod(),
                $this->getJoinTable()
            )->on(
                $localKey,
                $this->parentKey(Relation::INNER_KEY)
            );
        } else {
            // relation is loaded using external query
            $query->where($localKey, 'IN', new Parameter($outerKeys));
        }

        // user specified WHERE conditions
        $this->setWhere(
            $query,
            $this->getAlias(),
            $this->isJoined() ? 'onWhere' : 'where',
            $this->options['where'] ?? $this->schema[Relation::WHERE] ?? []
        );

        return parent::configureQuery($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function initNode(): AbstractNode
    {
        $node = new SingularNode(
            $this->columnNames(),
            $this->define(Schema::PRIMARY_KEY),
            $this->schema[Relation::OUTER_KEY],
            $this->schema[Relation::INNER_KEY]
        );

        $typecast = $this->define(Schema::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(new Typecast($typecast, $this->getSource()->getDatabase()));
        }

        return $node;
    }
}
