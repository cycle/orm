<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\ParentMergeNode;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;

/**
 * Load parent data.
 */
class ParentLoader extends JoinableLoader
{
    /**
     * Default set of relation options. Child implementation might defined their of default options.
     */
    protected array $options = [
        'load'      => true,
        'constrain' => true,
        'method'    => self::INLOAD,
        'minify'    => true,
        'as'        => null,
        'using'     => null,
    ];

    public function __construct(ORMInterface $orm, string $role, string $target)
    {
        $schema = $orm->getSchema();

        $schemaArray = [
            Relation::INNER_KEY => $schema->define($role, SchemaInterface::PRIMARY_KEY),
            Relation::OUTER_KEY => $schema->define($role, SchemaInterface::PARENT_KEY)
                ?? $schema->define($target, SchemaInterface::PRIMARY_KEY),
        ];
        $this->options['as'] ??= $target;
        parent::__construct($orm, $role, $target, $schemaArray);
    }

    protected function generateSublassLoaders(): iterable
    {
        return [];
    }

    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if ($this->options['using'] !== null) {
            // use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        $localPrefix = $this->getAlias() . '.';
        if ($this->isJoined()) {
            $parentKeys = (array)$this->schema[Relation::INNER_KEY];
            $parentPrefix = $this->parent->getAlias() . '.';
            $on = [];
            foreach ((array)$this->schema[Relation::OUTER_KEY] as $i => $key) {
                $field = $localPrefix . $this->fieldAlias($key);
                $on[$field] = $parentPrefix . $this->parent->fieldAlias($parentKeys[$i]);
            }
            $query->innerJoin($this->getJoinTable())->on($on);
        } else {
            // relation is loaded using external query
            $fields = array_map(
                // todo use static fn
                fn (string $key) => $localPrefix . $this->fieldAlias($key),
                (array)$this->schema[Relation::OUTER_KEY]
            );

            if (\count($fields) === 1) {
                $query->andWhere($fields[0], 'IN', new Parameter(array_column($outerKeys, key($outerKeys[0]))));
            } else {
                $query->andWhere(
                    static function (SelectQuery $select) use ($outerKeys, $fields) {
                        foreach ($outerKeys as $set) {
                            $select->orWhere(array_combine($fields, array_values($set)));
                        }
                    }
                );
            }
        }

        return parent::configureQuery($query);
    }

    protected function initNode(): AbstractNode
    {
        // throw new \RuntimeException('This method should not be called.');

        $node = new ParentMergeNode(
            $this->target,
            $this->columnNames(),
            (array)$this->define(SchemaInterface::PRIMARY_KEY),
            (array)$this->schema[Relation::OUTER_KEY],
            (array)$this->schema[Relation::INNER_KEY]
        );

        $typecast = $this->define(SchemaInterface::TYPECAST);
        if ($typecast !== null) {
            $node->setTypecast(new Typecast($typecast, $this->getSource()->getDatabase()));
        }

        return $node;
    }
}
