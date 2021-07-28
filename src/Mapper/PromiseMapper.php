<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Schema;
use Doctrine\Instantiator;
use Laminas\Hydrator;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\ReflectionHydrator;

/**
 * Provide the ability to carry data over the specific class instances. Supports table inheritance using
 * hidden entity field.
 */
class PromiseMapper extends DatabaseMapper
{
    // system column to store entity type
    public const ENTITY_TYPE = '_type';

    protected string $entity;

    protected array $children = [];

    protected HydratorInterface $hydrator;

    protected Instantiator\Instantiator $instantiator;

    public function __construct(ORMInterface $orm, string $role)
    {
        parent::__construct($orm, $role);

        $this->entity = $orm->getSchema()->define($role, Schema::ENTITY);
        $this->children = $orm->getSchema()->define($role, Schema::CHILDREN) ?? [];

        $this->hydrator = class_exists('Laminas\Hydrator\ReflectionHydrator')
            ? new ReflectionHydrator()
            : new Hydrator\Reflection();

        $this->instantiator = new Instantiator\Instantiator();
    }

    public function init(array $data): object
    {
        $class = $this->resolveClass($data);
        return $this->instantiator->instantiate($class);
    }

    public function hydrate(object $entity, array $data): object
    {
        // Force searching related entities in the Heap
        $relations = $this->orm->getRelationMap($this->role)->getRelations();
        foreach ($data as $k => $v) {
            if (!$v instanceof ReferenceInterface || !array_key_exists($k, $relations)) {
                continue;
            }
            $relation = $relations[$k];
            $relation->resolve($v, false);

            $data[$k] = $v->hasValue()
                ? $relation->collect($v->getValue())
                : new Promise($relation, $v);
        }

        return $this->hydrator->hydrate($data, $entity);
    }

    public function extract(object $entity): array
    {
        return $this->hydrator->extract($entity);
    }

    /**
     * Get entity columns.
     */
    public function fetchFields(object $entity): array
    {
        $columns = array_intersect_key($this->extract($entity), $this->columns);

        $class = get_class($entity);
        if ($class !== $this->entity) {
            // inheritance
            foreach ($this->children as $alias => $childClass) {
                if ($childClass == $class) {
                    $columns[self::ENTITY_TYPE] = $alias;
                    break;
                }
            }
        }

        return $columns;
    }

    public function fetchRelations(object $entity): array
    {
        return array_intersect_key(
            $this->extract($entity),
            $this->orm->getRelationMap($this->role)->getRelations()
        );
    }

    /**
     * Classname to represent entity.
     */
    protected function resolveClass(array $data): string
    {
        $class = $this->entity;
        if (!empty($this->children) && !empty($data[self::ENTITY_TYPE])) {
            $class = $this->children[$data[self::ENTITY_TYPE]] ?? $class;
        }

        return $class;
    }
}
