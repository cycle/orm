<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Mapper\Traits\SingleTableTrait;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\SchemaInterface;
use Doctrine\Instantiator;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\ReflectionHydrator;

/**
 * Provide the ability to carry data over the specific class instances. Supports table inheritance using
 * hidden entity field.
 */
class PromiseMapper extends DatabaseMapper
{
    use SingleTableTrait;

    protected string $entity;

    protected array $children = [];

    protected HydratorInterface $hydrator;

    protected Instantiator\Instantiator $instantiator;


    public function __construct(ORMInterface $orm, string $role)
    {
        parent::__construct($orm, $role);

        $this->schema = $orm->getSchema();
        $this->entity = $this->schema->define($role, SchemaInterface::ENTITY);
        $this->children = $this->schema->define($role, SchemaInterface::CHILDREN) ?? [];
        $this->discriminator = $this->schema->define($role, SchemaInterface::DISCRIMINATOR) ?? $this->discriminator;

        if (!isset($this->hydrator)) {
            $this->hydrator = new ReflectionHydrator();
        }

        $this->instantiator = new Instantiator\Instantiator();
    }

    public function init(array $data, string $role = null): object
    {
        $class = $this->resolveClass($data, $role);
        return $this->instantiator->instantiate($class);
    }

    public function hydrate(object $entity, array $data): object
    {
        // Force searching related entities in the Heap
        $relations = $this->relationMap->getRelations();
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
        $values = array_intersect_key($this->extract($entity), $this->columns + $this->parentColumns);
        return $values + $this->getDiscriminatorValues($entity);
    }

    public function fetchRelations(object $entity): array
    {
        return array_intersect_key(
            $this->extract($entity),
            $this->relationMap->getRelations()
        );
    }
}
