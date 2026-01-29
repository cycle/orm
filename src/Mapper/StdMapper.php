<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Reference\ReferenceInterface;

/**
 * Provide the ability to carry data over the StdClass objects. Does not support single table inheritance.
 */
final class StdMapper extends DatabaseMapper
{
    public function init(array $data, ?string $role = null): object
    {
        return new \stdClass();
    }

    public function hydrate($entity, array $data): object
    {
        $relations = $this->relationMap->getRelations();
        foreach ($data as $k => $v) {
            if ($v instanceof ReferenceInterface && \array_key_exists($k, $relations)) {
                $relation = $relations[$k];
                $relation->resolve($v, false);

                $entity->{$k} = $v->hasValue()
                    ? $relation->collect($v->getValue())
                    : new Promise($relation, $v);
                continue;
            }
            $entity->{$k} = $v;
        }

        return $entity;
    }

    public function extract($entity): array
    {
        return \get_object_vars($entity);
    }

    /**
     * Get entity columns.
     */
    public function fetchFields(object $entity): array
    {
        return \array_intersect_key(
            $this->extract($entity),
            $this->columns + $this->parentColumns,
        );
    }

    public function fetchRelations(object $entity): array
    {
        return \array_intersect_key(
            $this->extract($entity),
            $this->relationMap->getRelations(),
        );
    }
}
