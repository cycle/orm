<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select;

interface SourceProviderInterface
{
    /**
     * Get database source associated with given entity role.
     *
     * @param string $role
     * @return SourceInterface
     */
    public function getSource(string $role): SourceInterface;
}
