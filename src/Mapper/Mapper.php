<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Mapper;

use GeneratedHydrator\Configuration;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Schema;
use Zend\Hydrator;

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

    private static $hydrators = [];

    /**
     * @param ORMInterface $orm
     * @param string       $role
     */
    public function __construct(ORMInterface $orm, string $role)
    {
        parent::__construct($orm, $role);

        $this->entity = $orm->getSchema()->define($role, Schema::ENTITY);
        $this->children = $orm->getSchema()->define($role, Schema::CHILDREN) ?? [];

        // mappers can request custom hydrator using constructor dependency
        $this->hydrator = new Hydrator\Reflection();
    }

    /**
     * @inheritdoc
     */
    public function init(array $data): array
    {
        $class = $this->resolveClass($data);

        return [new $class, $data];
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

    protected function getHydrator($object): Hydrator\HydratorInterface
    {
        if (isset(self::$hydrators[get_class($object)])) {
            return self::$hydrators[get_class($object)];
        }

        $class = (new Configuration(get_class($object)))->createFactory()->getHydratorClass();

        return self::$hydrators[get_class($object)] = new $class;
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