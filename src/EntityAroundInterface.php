<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Select\SourceProviderInterface;

interface EntityAroundInterface extends SourceProviderInterface
{
    /**
     * Get mapper associated with given entity class, role or instance.
     */
    public function getMapper(string|object $entity): MapperInterface;

    /**
     * Get repository associated with given entity.
     */
    public function getRepository(string|object $entity): RepositoryInterface;

    /**
     * Get database source associated with given entity role.
     *
     * todo remove here or remove the SourceProviderInterface interface
     */
    public function getSource(string $role): SourceInterface;

    /**
     * Get relation map associated with given entity role.
     */
    public function getRelationMap(string $entity): RelationMap;

    /**
     * Get list of keys entity must be indexed in a Heap by.
     */
    public function getIndexes(string $role): array;
}
