<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Select\LoaderInterface;

/**
 * @internal
 */
abstract class AbstractMergeNode extends AbstractNode
{
    protected const OVERWRITE_DATA = false;

    protected array $results = [];

    /**
     * @param string[] $columns
     * @param string[] $primaryKeys
     * @param string[] $innerKeys Inner relation keys (for example user_id)
     * @param string[]|null $outerKeys Outer (parent) relation keys (for example id = parent.id)
     */
    public function __construct(
        private string $discriminatorValue,
        array $columns,
        array $primaryKeys,
        protected array $innerKeys,
        ?array $outerKeys,
    ) {
        parent::__construct($columns, $outerKeys);
        $this->setDuplicateCriteria($primaryKeys);
    }

    public function mergeInheritanceNodes(bool $includeRole = false): void
    {
        if ($this->parent === null) {
            return;
        }

        parent::mergeInheritanceNodes($includeRole);

        $roleField = $includeRole ? [LoaderInterface::ROLE_KEY => $this->discriminatorValue] : [];
        foreach ($this->results as $item) {
            if ($this->isEmptyKeys($this->innerKeys, $item)) {
                continue;
            }
            $this->parent->mergeData(
                $this->indexName,
                $this->intersectData($this->innerKeys, $item),
                $item + $roleField,
                static::OVERWRITE_DATA,
            );
        }
        $this->results = [];
    }

    protected function push(array &$data): void
    {
        $this->results[] = &$data;
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
