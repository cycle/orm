<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema;

/**
 * Locate entity declarations.
 */
interface LocatorInterface
{
    /**
     * Return all class declarations.
     *
     * @return EntityInterface[]
     */
    public function getDeclarations(): array;
}