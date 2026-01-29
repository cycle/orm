<?php

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
     */
    protected array $result = [];

    /**
     * Get resulted data tree.
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

    protected function push(array &$data): void
    {
        $this->result[] = &$data;
    }
}
