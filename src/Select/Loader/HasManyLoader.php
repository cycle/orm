<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\ArrayNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Traits\WhereTrait;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\SelectQuery;

class HasManyLoader extends JoinableLoader
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
        'method'    => self::POSTLOAD,
        'minify'    => true,
        'as'        => null,
        'using'     => null,
        'where'     => null,
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->options['where'] = $schema[Relation::WHERE] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->isLoaded() && $this->isJoined() && (int)$query->getLimit() !== 0) {
            throw new LoaderException('Unable to load data using join with limit on parent query');
        }

        if (!empty($this->options['using'])) {
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

        //User specified WHERE conditions
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
        $node = new ArrayNode(
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
