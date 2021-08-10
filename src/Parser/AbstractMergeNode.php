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

    /** @var string[] */
    protected array $innerKeys;

    protected array $results = [];

    private string $discriminatorValue;

    /**
     * @param array      $columns
     * @param array      $primaryKeys
     * @param array      $innerKeys Inner relation keys (for example user_id)
     * @param array|null $outerKeys Outer (parent) relation keys (for example id = parent.id)
     */
    public function __construct(
        string $discriminatorValue,
        array $columns,
        array $primaryKeys,
        array $innerKeys,
        ?array $outerKeys
    ) {
        parent::__construct($columns, $outerKeys);
        $this->setDuplicateCriteria($primaryKeys);

        $this->innerKeys = $innerKeys;
        $this->discriminatorValue = $discriminatorValue;
    }

    protected function push(array &$data): void
    {
        $this->results[] = &$data;
    }

    public function mergeInheritanceNodes(bool $withDiscriminator = false): bool
    {
        if ($this->parent === null) {
            return false;
        }

        parent::mergeInheritanceNodes();

        $discriminator = $withDiscriminator ? [LoaderInterface::DISCRIMINATOR_KEY => $this->discriminatorValue] : [];

        foreach ($this->results as $item) {
            if ($this->isEmptyKeys($this->innerKeys, $item)) {
                continue;
            }
            $this->parent->mergeData(
                $this->indexName,
                $this->intersectData($this->innerKeys, $item),
                $discriminator + $item,
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
