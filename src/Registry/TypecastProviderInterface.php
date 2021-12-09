<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry;

use Cycle\ORM\Parser\TypecastInterface;

interface TypecastProviderInterface
{
    /**
     * Get typecast implementation associated with given entity role.
     */
    public function getTypecast(string $role): ?TypecastInterface;
}
