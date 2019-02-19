<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\SingularNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Traits\WhereTrait;
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
        'constrain' => true,
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
            ['method' => $options['method'] ?? self::JOIN] + ($options['pivot'] ?? [])
        );

        return $loader;
    }

    /**
     * @param string $relation
     * @param array  $options
     * @param bool   $join
     * @return LoaderInterface
     */
    public function loadRelation(string $relation, array $options, bool $join = false): LoaderInterface
    {
        if ($relation == '@') {
            unset($options['method']);

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
        if (!empty($this->options['using'])) {
            // use pre-defined query
            return parent::configureQuery($this->pivot->configureQuery($query), $outerKeys);
        }

        // Manually join pivoted table
        if ($this->isJoined()) {
            $query->join(
                $this->getJoinMethod(),
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
            // reset all the columns when query is isolated (we have to do it manually
            // since underlying loader believes it's loaded)
            $query->columns([]);

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

        return parent::configureQuery($this->pivot->configureQuery($query));
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
     * {@inheritdoc}
     */
    protected function mountColumns(
        SelectQuery $query,
        bool $minify = false,
        string $prefix = '',
        bool $overwrite = false
    ): SelectQuery {
        // columns are reset on earlier stage to allow pivot loader mount it's own aliases
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