<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

use Cycle\ORM\RepositoryInterface;

interface RepositoryProviderInterface
{
    /**
     * Get repository associated with given entity role.
     *
     * @param non-empty-string $entity
     */
    public function getRepository(string $entity): RepositoryInterface;
}
