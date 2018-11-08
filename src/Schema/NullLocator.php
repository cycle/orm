<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Schema;

class NullLocator implements LocatorInterface
{
    /**
     * @inheritdoc
     */
    public function locateDeclarations(): array
    {
        return [];
    }
}