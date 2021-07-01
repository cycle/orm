<?php

declare(strict_types=1);

namespace Cycle\ORM;

interface EntityFactoryInterface
{
    /**
     * Create empty entity object an return pre-filtered data (hydration will happen on a later stage)
     */
    public function create(
        ORMInterface $orm,
        string $role,
        array $data,
        string $sourceClass
    ): object;

    public function upgrade(
        ORMInterface $orm,
        string $role,
        object $entity,
        array $data
    ): object;

    /**
     * Extract raw relations fields
     */
    public function extractRelations(RelationMap $relMap, object $entity): array;

    /**
     * Extract all data instead of relation fields
     */
    public function extractData(RelationMap $relMap, object $entity): array;
}
