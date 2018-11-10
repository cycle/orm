<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Schema;

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