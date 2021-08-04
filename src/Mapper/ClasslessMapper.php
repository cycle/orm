<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Mapper\Proxy\ClasslessProxyFactory;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RelationMap;

final class ClasslessMapper extends DatabaseMapper
{
    protected ClasslessProxyFactory $entityFactory;

    private RelationMap $relationMap;

    public function __construct(ORMInterface $orm, string $role)
    {
        parent::__construct($orm, $role);

        $this->entityFactory = new ClasslessProxyFactory();
        $this->relationMap = $orm->getRelationMap($role);
    }

    public function init(array $data): object
    {
        return $this->entityFactory->create($this->orm, $this->role, array_keys($this->columns + $this->parentColumns));
    }

    public function hydrate($entity, array $data): object
    {
        $this->entityFactory->upgrade($entity, $data);
        return $entity;
    }

    public function extract($entity): array
    {
        return $this->entityFactory->entityToArray($entity);
    }

    /**
     * Get entity columns.
     */
    public function fetchFields(object $entity): array
    {
        return array_intersect_key(
            $this->entityFactory->extractData($this->relationMap, $entity),
            $this->columns + $this->parentColumns
        );
    }

    public function fetchRelations(object $entity): array
    {
        return $this->entityFactory->extractRelations($this->relationMap, $entity);
    }
}
