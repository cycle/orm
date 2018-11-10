<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Schema\Record;

use Spiral\ORM\Schema\LocatorInterface;

class RecordLocator implements LocatorInterface
{
    /**
     * @inheritdoc
     */
    public function getDeclarations(): array
    {
        return [];
    }
}