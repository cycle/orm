<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

use Cycle\ORM\MapperInterface;

interface MapperProviderInterface
{
    /**
     * Get mapper associated with given entity role.
     *
     * @param non-empty-string $entity
     */
    public function getMapper(string $entity): MapperInterface;
}
