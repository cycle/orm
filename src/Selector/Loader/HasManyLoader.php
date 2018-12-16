<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector\Loader;

use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Parser\AbstractNode;
use Spiral\Cycle\Parser\ArrayNode;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector\JoinableLoader;
use Spiral\Cycle\Selector\SourceInterface;
use Spiral\Cycle\Selector\Traits\WhereTrait;
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
        'constrain'  => SourceInterface::DEFAULT_CONSTRAIN,
        'method' => self::POSTLOAD,
        'minify' => true,
        'alias'  => null,
        'using'  => null,
        'where'  => null,
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
    protected function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
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

        //When relation is joined we will use ON statements, when not - normal WHERE
        $whereTarget = $this->isJoined() ? 'onWhere' : 'where';

        //Where conditions specified in relation definition
        $this->setWhere($query, $this->getAlias(), $whereTarget, $this->define(Relation::WHERE));

        //User specified WHERE conditions
        $this->setWhere($query, $this->getAlias(), $whereTarget, $this->options['where']);

        return parent::configureQuery($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function initNode(): AbstractNode
    {
        return new ArrayNode(
            $this->getColumns(),
            $this->define(Schema::PRIMARY_KEY),
            $this->schema[Relation::OUTER_KEY],
            $this->schema[Relation::INNER_KEY]
        );
    }
}