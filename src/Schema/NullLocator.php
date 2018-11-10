<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Schema;

class NullLocator implements LocatorInterface
{
    /**
     * @inheritdoc
     */
    public function getDeclarations(): array
    {
        return [];
    }
}