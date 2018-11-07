<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Node;

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

    /**
     * {@inheritdoc}
     */
    protected function push(array &$data)
    {
        $this->result[] = &$data;
    }
}