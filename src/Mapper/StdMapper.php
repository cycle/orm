<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

/**
 * Provide the ability to carry data over the StdClass objects. Does not support single table inheritance.
 */
final class StdMapper extends DatabaseMapper
{
    public function init(array $data): array
    {
        return [new \stdClass(), $data];
    }

    public function hydrate($entity, array $data): object
    {
        foreach ($data as $k => $v) {
            $entity->{$k} = $v;
        }

        return $entity;
    }

    public function extract($entity): array
    {
        return get_object_vars($entity);
    }

    /**
     * Get entity columns.
     */
    public function fetchFields(object $entity): array
    {
        return array_intersect_key(
            $this->extract($entity),
            array_flip($this->columns)
        );
    }
}
