<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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