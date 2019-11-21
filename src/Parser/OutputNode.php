<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * Provides ability to fetch node context.
 *
 * @internal
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
     * Destructing.
     */
    public function __destruct()
    {
        $this->result = [];
        parent::__destruct();
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
     * {@inheritdoc}
     */
    protected function push(array &$data): void
    {
        $this->result[] = &$data;
    }
}
