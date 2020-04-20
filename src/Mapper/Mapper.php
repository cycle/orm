<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Doctrine\Instantiator;
use Laminas\Hydrator;

/**
 * Provide the ability to carry data over the specific class instances. Supports table inheritance using
 * hidden entity field.
 */
class Mapper extends DatabaseMapper
{
    // system column to store entity type
    public const ENTITY_TYPE = '_type';

    /** @var string */
    protected $entity;

    /** @var array */
    protected $children = [];

    /** @var Hydrator\HydratorInterface */
    protected $hydrator;

    /** @var Instantiator\InstantiatorInterface */
    protected $instantiator;

    /**
     * @param ORMInterface $orm
     * @param string       $role
     */
    public function __construct(ORMInterface $orm, string $role)
    {
        parent::__construct($orm, $role);

        $this->entity = $orm->getSchema()->define($role, Schema::ENTITY);
        $this->children = $orm->getSchema()->define($role, Schema::CHILDREN) ?? [];

        $this->hydrator = class_exists('Laminas\Hydrator\ReflectionHydrator')
            ? new Hydrator\ReflectionHydrator()
            : new Hydrator\Reflection();

        $this->instantiator = new Instantiator\Instantiator();
    }

    /**
     * @inheritdoc
     */
    public function init(array $data): array
    {
        $class = $this->resolveClass($data);

        return [$this->instantiator->instantiate($class), $data];
    }

    /**
     * @inheritdoc
     */
    public function hydrate($entity, array $data)
    {
        return $this->hydrator->hydrate($data, $entity);
    }

    /**
     * @inheritdoc
     */
    public function extract($entity): array
    {
        return $this->hydrator->extract($entity);
    }

    /**
     * Get entity columns.
     *
     * @param object $entity
     * @return array
     */
    protected function fetchFields($entity): array
    {
        $columns = array_intersect_key($this->extract($entity), array_flip($this->fields));

        $class = get_class($entity);
        if ($class != $this->entity) {
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
     *
     * @param array $data
     * @return string
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
