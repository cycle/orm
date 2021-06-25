<?php

declare(strict_types=1);

namespace Cycle\ORM;

interface EntityFactoryInterface
{
    /**
     * Create empty entity
     */
    public function create(
        ORMInterface $orm,
        string $role,
        RelationMap $relMap,
        array $data
    ): object;

    public function upgrade(
        ORMInterface $orm,
        string $role,
        object $entity,
        array $data
    ): object;
}
