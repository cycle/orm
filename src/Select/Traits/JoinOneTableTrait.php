<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Relation;

/**
 * @internal
 */
trait JoinOneTableTrait
{
    private function configureParentQuery(SelectQuery $query, array $outerKeys = []): void
    {
        if ($this->isJoined()) {
            $this->configureJoinedQuery($query);
        } elseif ($outerKeys !== []) {
            // relation is loaded using external query
            $this->configureSeparatedQuery($query, $outerKeys);
        }
    }

    private function configureJoinedQuery(SelectQuery $query): void
    {
        $localPrefix = $this->getAlias() . '.';
        $parentPrefix = $this->parent->getAlias() . '.';
        $parentKeys = (array) $this->schema[Relation::INNER_KEY];

        $on = [];
        foreach ((array) $this->schema[Relation::OUTER_KEY] as $i => $key) {
            $field = $localPrefix . $this->fieldAlias($key);
            $on[$field] = $parentPrefix . $this->parent->fieldAlias($parentKeys[$i]);
        }

        $query->join(
            $this->getJoinMethod(),
            $this->getJoinTable(),
        )->on($on);
    }

    private function configureSeparatedQuery(SelectQuery $query, array $outerKeys): void
    {
        $localPrefix = $this->getAlias() . '.';

        $fields = [];
        foreach ((array) $this->schema[Relation::OUTER_KEY] as $key) {
            $fields[] = $localPrefix . $this->fieldAlias($key);
        }

        if (\count($fields) === 1) {
            $query->andWhere($fields[0], 'IN', new Parameter(\array_column($outerKeys, \key($outerKeys[0]))));
        } else {
            $query->andWhere(
                static function (SelectQuery $select) use ($outerKeys, $fields): void {
                    foreach ($outerKeys as $set) {
                        $select->orWhere(\array_combine($fields, \array_values($set)));
                    }
                },
            );
        }
    }
}
