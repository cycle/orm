<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

/**
 * Provides ability to calculate number of object claims.
 */
trait ClaimTrait
{
    private int $numClaims = 1;

    /**
     * Add reference to the related entity.
     */
    public function addClaim(): void
    {
        $this->numClaims++;
    }

    /**
     * Un-claim reference to the related entity.
     */
    public function decClaim(): void
    {
        $this->numClaims--;
    }

    /**
     * Check if related entity has any references.
     */
    public function hasClaims(): bool
    {
        return $this->numClaims > 0;
    }
}
