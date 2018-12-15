<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Cycle\Parser;

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
     * {@inheritdoc}
     */
    protected function push(array &$data)
    {
        $this->result[] = &$data;
    }

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