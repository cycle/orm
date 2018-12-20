<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Mapper;

use Spiral\Cycle\ORMInterface;
use Zend\Hydrator;

/**
 * Provide the ability to carry data over the specific class instances. Supports table inheritance using
 * hidden entity field.
 */
class Mapper extends DatabaseMapper
{
    // system column to store entity type
    public const ENTITY_TYPE = '_type';

    /** @var Hydrator\HydratorInterface */
    protected $hydrator;

    /**
     * @param ORMInterface $orm
     * @param string       $role
     */
    public function __construct(ORMInterface $orm, string $role)
    {
        parent::__construct($orm, $role);

        $this->hydrator = new Hydrator\Reflection();
    }

    /**
     * @inheritdoc
     */
    public function init(array $data): array
    {
        $class = $this->resolveClass($data);

        // filter data

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

    /**
     * Get entity columns.
     *
     * @param object $entity
     * @return array
     */
    protected function fetchColumns($entity): array
    {
        $columns = array_intersect_key($this->extract($entity), array_flip($this->columns));

        $class = get_class($entity);
        if ($class != $this->role) {
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
        $class = $this->role;
        if (!empty($this->children) && !empty($data[self::ENTITY_TYPE])) {
            $class = $this->children[$data[self::ENTITY_TYPE]] ?? $class;
        }

        return $class;
    }
}