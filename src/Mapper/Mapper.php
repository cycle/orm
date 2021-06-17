<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Doctrine\Instantiator;
use Laminas\Hydrator;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\ReflectionHydrator;

/**
 * Provide the ability to carry data over the specific class instances. Supports table inheritance using
 * hidden entity field.
 */
class Mapper extends DatabaseMapper
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

    public function init(array $data): array
    {
        $class = $this->resolveClass($data);

        return [$this->instantiator->instantiate($class), $data];
    }

    public function hydrate(object $entity, array $data): object
    {
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
        $columns = array_intersect_key($this->extract($entity), array_flip($this->fields));

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
