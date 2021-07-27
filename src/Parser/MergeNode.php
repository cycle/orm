<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * @internal
 */
final class MergeNode extends AbstractNode
{
    /** @var string[] */
    protected array $innerKeys;

    protected array $results = [];

    /**
     * @param array      $columns
     * @param array      $primaryKeys
     * @param array      $innerKeys Inner relation keys (for example user_id)
     * @param array|null $outerKeys Outer (parent) relation keys (for example id = parent.id)
     */
    public function __construct(array $columns, array $primaryKeys, array $innerKeys, ?array $outerKeys)
    {
        parent::__construct($columns, $outerKeys);
        $this->setDuplicateCriteria($primaryKeys);

        $this->innerKeys = $innerKeys;
    }

    protected function push(array &$data): void
    {
        $this->results[] = &$data;
    }

    public function mergeInheritanceNode(): void
    {
        if ($this->parent === null) {
            return;
        }

        parent::mergeInheritanceNode();

        foreach ($this->results as $item) {
            $this->parent->mergeData(
                $this->indexName,
                $this->intersectData($this->innerKeys, $item),
                $item,
                false
            );
        }
        $this->results = [];
    }
}
