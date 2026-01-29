<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\ParserException;
use Cycle\Database\Injection\Parameter;

/**
 * @internal
 */
final class MultiKeyCollection
{
    /**
     * [Index] = [key1, key2, ...]
     *
     * @var string[][]
     */
    private array $indexes = [];

    /**
     * [Index][key1-value][key2-value][...] = [ITEM1, ITEM2, ...].
     */
    private array $data = [];

    /**
     * Contains key values of last added item for each Index
     * [index] = [key1-value, key2-value, ...]
     */
    private array $lastItemKeys = [];

    public function createIndex(string $name, array $keys): void
    {
        $this->indexes[$name] = $keys;
        $this->data[$name] = [];
    }

    public function getIndexes(): array
    {
        return \array_keys($this->indexes);
    }

    public function hasIndex(string $index): bool
    {
        return \array_key_exists($index, $this->indexes);
    }

    public function getItemsCount(string $index, array $values): int
    {
        try {
            return \count($this->getValues($this->data[$index], $values));
        } catch (\Exception) {
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
            if (!\is_scalar($keyValue)) {
                throw new \InvalidArgumentException("Invalid value on the key `$key`.");
            }
            $itemKeys[] = $keyValue;
            if (!\array_key_exists($keyValue, $pool)) {
                $pool[$keyValue] = [];
            }
            $pool = &$pool[$keyValue];
        }
        $pool[] = &$data;
        $this->lastItemKeys[$index] = $itemKeys;
    }

    public function getCriteria(string $index, bool $useParameter = false): array
    {
        $result = [];
        foreach ($this->data[$index] as $key => $data) {
            $base = [$this->indexes[$index][0] => $key];
            $result[] = $this->extractAssoc($data, $base, $this->indexes[$index], 1, true);
        }
        return $result === [] ? [] : \array_merge(...$result);
    }

    public function getItems(string $indexName): \Generator
    {
        $depth = \count($this->indexes[$indexName]);

        $iterator = static function (array $data, $deep) use (&$depth, &$iterator) {
            if ($deep < $depth) {
                ++$deep;
                foreach ($data as $subset) {
                    yield from $iterator($subset, $deep);
                }
                return;
            }
            yield from $data;
        };
        yield from $iterator($this->data[$indexName], 1);
    }

    /**
     * @param string[] $keys
     */
    private function extractAssoc(array $data, array $base, array $keys, int $level, bool $useParameter): array
    {
        // Optimization. Group last column values into single Parameter.
        // For example, where condition will look like:
        // key1="1" AND key2 IN (1, 2, 3)
        // instead of:
        // (key1="1" AND key2="1") OR (key1="1" AND key2="2") OR (key1="1" AND key2="3")
        if ($useParameter && $level === \count($keys) - 1 && \count($data) > 1) {
            return [$base + [$keys[$level] => new Parameter(\array_keys($data))]];
        }

        if ($level >= \count($keys)) {
            return [$base];
        }
        $result = [];
        $field = $keys[$level];
        foreach ($data as $key => $value) {
            $base[$field] = $key;
            $result[] = $this->extractAssoc($value, $base, $keys, $level + 1, $useParameter);
        }
        return \array_merge(...$result);
    }

    private function getValues(array &$dataSet, array $keys): array
    {
        $value = &$dataSet;
        foreach ($keys as $key) {
            if (!\array_key_exists($key, $value)) {
                throw new ParserException('Value not found.');
            }
            $value = &$value[$key];
        }
        return $value;
    }
}
