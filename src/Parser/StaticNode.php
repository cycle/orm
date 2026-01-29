<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * This node is used when entity data is already loaded.
 *
 * @internal
 */
final class StaticNode extends OutputNode
{
    /**
     * @param non-empty-string[] $columns
     * @param non-empty-string[] $primaryKeys
     */
    public function __construct(array $columns, array $primaryKeys)
    {
        parent::__construct($columns, null);
        $this->setDuplicateCriteria($primaryKeys);
    }

    public function push(array &$data): void
    {
        parent::push($data);
        foreach ($this->indexedData->getIndexes() as $index) {
            try {
                $this->indexedData->addItem($index, $data);
            } catch (\Throwable) {
            }
        }

        // Let's force placeholders for every sub loaded
        foreach ($this->nodes as $name => $node) {
            if ($node instanceof ParentMergeNode) {
                continue;
            }
            $data[$name] = $node instanceof ArrayNode ? [] : null;
        }
    }
}
