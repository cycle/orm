<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Mapper;

/**
 * Provide the ability to carry data over the StdClass objects. Does not support single table inheritance.
 */
class StdMapper extends DatabaseMapper
{
    /**
     * @inheritdoc
     */
    public function init(array $data): array
    {
        return [new \stdClass(), $data];
    }

    /**
     * @inheritdoc
     */
    public function hydrate($entity, array $data)
    {
        foreach ($data as $k => $v) {
            $entity->{$k} = $v;
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function extract($entity): array
    {
        return (array)$entity;
    }

    /**
     * Get entity columns.
     *
     * @param object $entity
     * @return array
     */
    protected function fetchFields($entity): array
    {
        return array_intersect_key($this->extract($entity), array_flip($this->columns));
    }
}