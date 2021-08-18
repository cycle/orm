<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Mapper\Traits\SingleTableTrait;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Mapper\Proxy\ProxyEntityFactory;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;

/**
 * Provide the ability to carry data over the specific class instances using proxy classes.
 *
 * Supports table inheritance using hidden entity field.
 */
class Mapper extends DatabaseMapper
{
    use SingleTableTrait;

    /** @var class-string */
    protected string $entity;

    protected array $children = [];

    protected ProxyEntityFactory $entityFactory;

    private RelationMap $relationMap;

    public function __construct(ORMInterface $orm, ProxyEntityFactory $entityFactory, string $role)
    {
        parent::__construct($orm, $role);

        $this->entity = $orm->getSchema()->define($role, SchemaInterface::ENTITY);
        $this->children = $orm->getSchema()->define($role, SchemaInterface::CHILDREN) ?? [];
        $this->entityFactory = $entityFactory;
        $this->relationMap = $orm->getRelationMap($role);
        $this->discriminator = $orm->getSchema()->define($role, SchemaInterface::DISCRIMINATOR) ?? $this->discriminator;
    }

    public function init(array $data, string $role = null): object
    {
        $class = $this->resolveClass($data, $role);
        return $this->entityFactory->create($this->orm, $class, $data, $class);
    }

    public function hydrate(object $entity, array $data): object
    {
        $this->entityFactory->upgrade($this->relationMap, $entity, $data);
        return $entity;
    }

    public function extract(object $entity): array
    {
        return $this->entityFactory->extractData($this->relationMap, $entity)
            + $this->entityFactory->extractRelations($this->relationMap, $entity);
    }

    public function fetchFields(object $entity): array
    {
        $values = array_intersect_key(
            $this->entityFactory->extractData($this->relationMap, $entity),
            $this->columns + $this->parentColumns
        );
        return $values + $this->getDiscriminatorValues($entity);
    }

    public function fetchRelations(object $entity): array
    {
        return $this->entityFactory->extractRelations($this->relationMap, $entity);
    }
}
