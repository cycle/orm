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
use Cycle\ORM\Parser\SingularNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Traits\WhereTrait;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\StatementInterface;

class ManyToManyLoader extends JoinableLoader
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
        'pivot'     => null
    ];

    /** @var PivotLoader */
    protected $pivot;

    /**
     * {@inheritdoc}
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->pivot = new PivotLoader($orm, 'pivot', $schema[Relation::THROUGH_ENTITY], $schema);
    }

    /**
     * Make sure that pivot loader is always carried with parent relation.
     */
    public function __clone()
    {
        parent::__clone();
        $this->pivot = clone $this->pivot;
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
            [
                'load'   => $loader->isLoaded(),
                'method' => $options['method'] ?? self::JOIN,
            ] + ($options['pivot'] ?? [])
        );

        return $loader;
    }

    /**
     * @param string $relation
     * @param array  $options
     * @param bool   $join
     * @param bool   $load
     * @return LoaderInterface
     */
    public function loadRelation(
        string $relation,
        array $options,
        bool $join = false,
        bool $load = false
    ): LoaderInterface {
        if ($relation === '@' || $relation === '@.@') {
            unset($options['method']);
            if ($options !== []) {
                // re-configure
                $this->pivot = $this->pivot->withContext($this, $options);
            }

            return $this->pivot;
        }

        return parent::loadRelation($relation, $options, $join, $load);
    }

    /**
     * {@inheritdoc}
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->isLoaded() && $this->isJoined() && (int) $query->getLimit() !== 0) {
            throw new LoaderException('Unable to load data using join with limit on parent query');
        }

        if ($this->options['using'] !== null) {
            // use pre-defined query
            return parent::configureQuery($this->pivot->configureQuery($query), $outerKeys);
        }

        // Manually join pivoted table
        if ($this->isJoined()) {
            $query->join(
                $this->getJoinMethod(),
                $this->pivot->getJoinTable()
            )->on(
                $this->pivot->localKey(Relation::THROUGH_INNER_KEY),
                $this->parentKey(Relation::INNER_KEY)
            );

            $query->innerJoin(
                $this->getJoinTable()
            )->on(
                $this->localKey(Relation::OUTER_KEY),
                $this->pivot->localKey(Relation::THROUGH_OUTER_KEY)
            );
        } else {
            // reset all the columns when query is isolated (we have to do it manually
            // since underlying loader believes it's loaded)
            $query->columns([]);

            $query->innerJoin(
                $this->pivot->getJoinTable()
            )->on(
                $this->pivot->localKey(Relation::THROUGH_OUTER_KEY),
                $this->localKey(Relation::OUTER_KEY)
            )->where(
                $this->pivot->localKey(Relation::THROUGH_INNER_KEY),
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
     * @param AbstractNode $node
     */
    protected function loadChild(AbstractNode $node): void
    {
        $node = $node->getNode('@');
        foreach ($this->load as $relation => $loader) {
            $loader->loadData($node->getNode($relation));
        }
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
            $this->schema[Relation::THROUGH_OUTER_KEY]
        );

        $typecast = $this->define(Schema::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(new Typecast($typecast, $this->getSource()->getDatabase()));
        }

        return $node;
    }
}
