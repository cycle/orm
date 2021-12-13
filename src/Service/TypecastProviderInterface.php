<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

use Cycle\ORM\Parser\TypecastInterface;

interface TypecastProviderInterface
{
    /**
     * Get typecast implementation associated with given entity role.
     *
     * @param non-empty-string $role
     */
    public function getTypecast(string $role): ?TypecastInterface;
}
