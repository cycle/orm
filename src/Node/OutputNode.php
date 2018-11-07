<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Node;

/**
 * Provides ability to fetch node context.
 */
abstract class OutputNode extends AbstractNode
{
    /**
     * Array used to aggregate all nested node results in a form of tree.
     *
     * @var array
     */
    protected $result = [];

    /**
     * Get resulted data tree.
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Destructing.
     */
    public function __destruct()
    {
        $this->result = [];
        parent::__destruct();
    }
}