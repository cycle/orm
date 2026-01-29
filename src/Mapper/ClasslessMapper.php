<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Mapper\Proxy\ClasslessProxyFactory;
use Cycle\ORM\ORMInterface;

final class ClasslessMapper extends DatabaseMapper
{
    protected ClasslessProxyFactory $entityFactory;

    public function __construct(ORMInterface $orm, string $role)
    {
        parent::__construct($orm, $role);

        $this->entityFactory = new ClasslessProxyFactory();
    }

    public function init(array $data, ?string $role = null): object
    {
        return $this->entityFactory->create($this->relationMap, $this->role, \array_keys($this->columns + $this->parentColumns));
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
        return \array_intersect_key(
            $this->entityFactory->extractData($this->relationMap, $entity),
            $this->columns + $this->parentColumns,
        );
    }

    public function fetchRelations(object $entity): array
    {
        return $this->entityFactory->extractRelations($this->relationMap, $entity);
    }
}
