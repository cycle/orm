<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

interface SourceProviderInterface
{
    /**
     * Get database source associated with given entity role.
     */
    public function getSource(string $role): SourceInterface;
}
