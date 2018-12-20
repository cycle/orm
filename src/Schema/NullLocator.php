<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

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