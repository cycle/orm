<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry;

interface IndexProviderInterface
{
    /**
     * Get list of keys entity must be indexed in a Heap by.
     */
    public function getIndexes(string $role): array;
}
