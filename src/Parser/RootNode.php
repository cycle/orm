<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * Node without specified parent.
 *
 * @internal
 */
final class RootNode extends OutputNode
{
    /**
     * @param string[] $columns
     * @param string[] $primaryKeys
     */
    public function __construct(array $columns, array $primaryKeys)
    {
        parent::__construct($columns, null);
        $this->setDuplicateCriteria($primaryKeys);
    }
}
