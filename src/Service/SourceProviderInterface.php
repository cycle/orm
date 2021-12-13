<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

use Cycle\ORM\Select\SourceInterface;

// todo move back into Select namespace?
interface SourceProviderInterface
{
    /**
     * Get database source associated with given entity role.
     */
    public function getSource(string $entity): SourceInterface;
}
