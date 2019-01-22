<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema;

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