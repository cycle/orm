<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\ParserException;

/**
 * @internal
 */
final class EmbeddedNode extends AbstractNode
{
    /**
     * @param array $data
     */
    protected function push(array &$data): void
    {
        if ($this->parent === null) {
            throw new ParserException('Unable to register data tree, parent is missing');
        }

        $this->parent->mount(
            $this->container,
            $this->outerKey,
            self::LAST_REFERENCE,
            $data
        );
    }
}
