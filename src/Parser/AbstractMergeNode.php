<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * @internal
 */
abstract class AbstractMergeNode extends AbstractNode
{
    protected const OVERWRITE_DATA = false;

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

    public function mergeInheritanceNodes(): bool
    {
        if ($this->parent === null) {
            return false;
        }

        parent::mergeInheritanceNodes();

        foreach ($this->results as $item) {
            if ($this->isEmptyKeys($this->innerKeys, $item)) {
                continue;
            }
            $this->parent->mergeData(
                $this->indexName,
                $this->intersectData($this->innerKeys, $item),
                $item,
                self::OVERWRITE_DATA
            );
        }
        $this->results = [];
        return true;
    }

    /**
     * @psalm-pure
     */
    private function isEmptyKeys(array $keys, array $data): bool
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                return false;
            }
        }
        return true;
    }
}
