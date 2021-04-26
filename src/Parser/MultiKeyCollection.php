<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\ParserException;

/**
 * @internal
 */
final class MultiKeyCollection
{
    /**
     * [Index] = [key1, key2, ...]
     * @var string[][]
     */
    private $indexes = [];

    /**
     * [Index][key1-value][key2-value][...] = [ITEM1, ITEM2, ...].
     */
    private $data = [];

    /**
     * Contains key values of last added item for each Index
     * [index] = [key1-value, key2-value, ...]
     */
    private $lastItemKeys = [];

    public function createIndex(string $name, array $keys): void
    {
        $this->indexes[$name] = $keys;
        $this->data[$name] = [];
    }

    public function getIndexes(): array
    {
        return array_keys($this->indexes);
    }

    public function hasIndex(string $outerKey): bool
    {
        return array_key_exists($outerKey, $this->indexes);
    }

    public function getIndex(string $indexName): array
    {
        return $this->indexes[$indexName] ?? [];
    }

    public function getItemsCount(string $index, array $values): int
    {
        try {
            return count($this->getValues($this->data[$index], $values));
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getItemsSubset(string $index, array $values): array
    {
        return $this->getValues($this->data[$index], $values);
    }

    public function getLastItemKeys(string $index): array
    {
        return $this->lastItemKeys[$index];
    }

    public function addItem(string $index, array &$data): void
    {
        $pool = &$this->data[$index];
        $itemKeys = [];
        foreach ($this->indexes[$index] as $key) {
            $keyValue = $data[$key] ?? null;
            if (!is_scalar($keyValue)) {
                throw new \InvalidArgumentException("Invalid value on the key `$key`.");
            }
            $itemKeys[] = $keyValue;
            if (!array_key_exists($keyValue, $pool)) {
                $pool[$keyValue] = [];
            }
            $pool = &$pool[$keyValue];
        }
        $pool[] = &$data;
        $this->lastItemKeys[$index] = $itemKeys;
        // return count($pull);
    }

    public function getIndexAssoc(string $index): array
    {
        $result = [];
        foreach ($this->data[$index] as $key => $data) {
            $base = [$this->indexes[$index][0] => $key];
            $result[] = $this->extractAssoc($data, $base, $this->indexes[$index], 1);
        }
        return $result === [] ? [] : array_merge(...$result);
    }

    /**
     * @param string[] $keys
     */
    private function extractAssoc(array $data, array $base, array $keys, int $level): array
    {
        if ($level >= count($keys)) {
            return [$base];
        }
        $result = [];
        foreach ($data as $key => $value) {
            $base[$keys[$level]] = $key;
            $result[] = $this->extractAssoc($value, $base, $keys, $level + 1);
        }
        return array_merge(...$result);
    }

    private function getValues(array &$dataSet, array $keys): array
    {
        $value = &$dataSet;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $value)) {
                throw new ParserException("Value not found.");
            }
            $value = &$value[$key];
        }
        return $value;
    }
}
