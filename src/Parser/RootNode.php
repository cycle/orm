<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Cycle\Parser;

/**
 * Node without specified parent.
 */
class RootNode extends OutputNode
{
    /**
     * @param array  $columns
     * @param string $primaryKey
     */
    public function __construct(array $columns, string $primaryKey)
    {
        parent::__construct($columns, null);
        $this->setDuplicateCriteria($primaryKey);
    }
}