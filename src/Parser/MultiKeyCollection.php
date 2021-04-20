<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

final class MultiKeyCollection
{
    /** @var string[][] */
    private $indexes = [];

    private $data = [];

    private $lastItem = [];

    public function hasIndex(string $outerKey): bool
    {
        return array_key_exists($outerKey, $this->indexes);
    }

    public function createIndex(string $name, array $keys): void
    {
        $this->indexes[$name] = $keys;
        $this->data[$name] = [];
    }

    public function getIndexes(): array
    {
        return array_keys($this->indexes);
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

    public function &getLastItemKeys(string $index)
    {
        return $this->lastItem[$index];
    }

    public function addItem(string $index, array &$data): void
    {
        $pull = &$this->data[$index];
        $itemKeys = [];
        foreach ($this->indexes[$index] as $key) {
            $keyValue = $data[$key] ?? null;
            if (!is_scalar($keyValue)) {
                throw new \InvalidArgumentException("Invalid value on the key `$key`.");
            }
            $itemKeys[] = $keyValue;
            $pull = &$pull[$keyValue];
        }
        $pull[] = &$data;
        $this->lastItem[$index] = $itemKeys;
        // return count($pull);
    }

    public function getIndexAssoc(string $index): array
    {
        $result = [];
        foreach ($this->data[$index] as $key => $data) {
            $base = [$this->indexes[$index][0] => $key];
            $result[] = $this->extractAssoc($data, $base, $this->indexes[$index], 1);
        }
        return array_merge(...$result);
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
                throw new \RuntimeException('Value not found.');
            }
            $value = &$value[$key];
        }
        return $value;
    }
}