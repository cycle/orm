<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select\Loader;

use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Parser\AbstractNode;
use Spiral\Cycle\Parser\SingularNode;
use Spiral\Cycle\Parser\Typecast;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select\JoinableLoader;
use Spiral\Cycle\Select\LoaderInterface;
use Spiral\Cycle\Select\SourceInterface;
use Spiral\Cycle\Select\Traits\WhereTrait;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\SelectQuery;

class ManyToManyLoader extends JoinableLoader
{
    use WhereTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'constrain' => SourceInterface::DEFAULT_CONSTRAIN,
        'method'    => self::POSTLOAD,
        'minify'    => true,
        'as'        => null,
        'using'     => null,
        'where'     => null,
    ];

    /** @var PivotLoader */
    protected $pivot;

    /**
     * {@inheritdoc}
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);

        unset($schema[Relation::CONSTRAIN]);

        //todo: make this relation to be real?

        // todo: extract pivot options
        $this->pivot = new PivotLoader($orm, 'pivot', $schema[Relation::THOUGHT_ENTITY], $schema);
    }

    /**
     * @param LoaderInterface $parent
     * @param array           $options
     * @return LoaderInterface
     */
    public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface
    {
        /** @var ManyToManyLoader $loader */
        $loader = parent::withContext($parent, $options);
        $loader->pivot = $loader->pivot->withContext(
            $loader,
            ['method' => $options['method'] ?? self::JOIN]
        );

        return $loader;
    }

    public function loadRelation(string $relation, array $options, bool $join = false): LoaderInterface
    {
        // todo crazy stuff is possible now (!)
        if ($relation == 'pivot') {
            if (!empty($options)) {
                $this->pivot = $this->pivot->withContext($this, $options);
            }

            return $this->pivot;
        }

        return parent::loadRelation($relation, $options, $join);
    }

    /**
     * {@inheritdoc}
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        // use pre-defined query
        if (!empty($this->options['using'])) {
            return parent::configureQuery($query, $outerKeys);
        }

        $query = $this->pivot->applyConstrain($query);

        // Manually join pivoted table
        if ($this->isJoined()) {
            $query->join(
                $this->pivot->getJoinMethod(),
                $this->pivot->getJoinTable()
            )->on(
                $this->pivot->localKey(Relation::THOUGHT_INNER_KEY),
                $this->parentKey(Relation::INNER_KEY)
            );

            $query->innerJoin(
                $this->getJoinTable()
            )->on(
                $this->localKey(Relation::OUTER_KEY),
                $this->pivot->localKey(Relation::THOUGHT_OUTER_KEY)
            );
        } else {
            $query->innerJoin(
                $this->pivot->getJoinTable()
            )->on(
                $this->pivot->localKey(Relation::THOUGHT_OUTER_KEY),
                $this->localKey(Relation::OUTER_KEY)
            )->where(
                $this->pivot->localKey(Relation::THOUGHT_INNER_KEY),
                new Parameter($outerKeys)
            );
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
    public function createNode(): AbstractNode
    {
        $node = $this->pivot->createNode();
        $node->joinNode('@', parent::createNode());

        return $node;
    }

    /**
     * Load columns from both pivot and target entities.
     *
     * @param SelectQuery $query
     * @param bool        $minify
     * @param string      $prefix
     * @param bool        $overwrite
     * @return SelectQuery
     */
    protected function mountColumns(
        SelectQuery $query,
        bool $minify = false,
        string $prefix = '',
        bool $overwrite = false
    ): SelectQuery {
        $this->pivot->mountColumns($query, $minify, $prefix, $overwrite);

        return parent::mountColumns($query, $minify, $prefix, false);
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
            $this->schema[Relation::THOUGHT_OUTER_KEY]
        );

        $typecast = $this->define(Schema::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(new Typecast($typecast, $this->getSource()->getDatabase()));
        }

        return $node;
    }
}