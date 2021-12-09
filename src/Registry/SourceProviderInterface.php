<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry;

use Cycle\ORM\Select\SourceInterface;

interface SourceProviderInterface
{
    /**
     * Get database source associated with given entity class or role.
     */
    public function getSource(string $entity): SourceInterface;
}
