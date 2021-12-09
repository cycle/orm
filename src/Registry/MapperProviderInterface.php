<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry;

use Cycle\ORM\MapperInterface;

interface MapperProviderInterface
{
    /**
     * Get mapper associated with given entity role.
     */
    public function getMapper(string $role): MapperInterface;
}
