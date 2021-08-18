<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * @internal
 */
class ParentMergeNode extends AbstractMergeNode
{
    protected const OVERWRITE_DATA = false;

    public function mergeInheritanceNodes(bool $includeRole = false): void
    {
        parent::mergeInheritanceNodes(false);
    }
}
