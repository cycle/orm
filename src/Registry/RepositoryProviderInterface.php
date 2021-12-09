<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry;

use Cycle\ORM\RepositoryInterface;

interface RepositoryProviderInterface
{
    /**
     * Get repository associated with given entity role.
     */
    public function getRepository(string $role): RepositoryInterface;
}
