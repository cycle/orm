<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

interface SourceProviderInterface
{
    /**
     * Get database source associated with given entity class or role.
     */
    public function getSource(string $entity): SourceInterface;
}
