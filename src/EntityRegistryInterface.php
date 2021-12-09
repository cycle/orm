<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Registry\SourceProviderInterface;
use Cycle\ORM\Select\SourceInterface;

/**
 * todo: optimize this interface in the {@see ORM} class
 */
interface EntityRegistryInterface extends SourceProviderInterface
{
    /**
     * Get mapper associated with given entity role.
     */
    public function getMapper(string $role): MapperInterface;

    /**
     * Get repository associated with given entity role.
     */
    public function getRepository(string $role): RepositoryInterface;

    /**
     * Get typecast implementation associated with given entity role.
     */
    public function getTypecast(string $role): ?TypecastInterface;

    /**
     * Get database source associated with given entity role.
     *
     * todo remove here or remove the SourceProviderInterface interface
     */
    public function getSource(string $role): SourceInterface;

    /**
     * Get relation map associated with given entity role.
     */
    public function getRelationMap(string $role): RelationMap;

    /**
     * Get list of keys entity must be indexed in a Heap by.
     */
    public function getIndexes(string $role): array;
}
