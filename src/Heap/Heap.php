<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap;

final class Heap implements HeapInterface, \IteratorAggregate
{
    private const INDEX_KEY_SEPARATOR = ':';

    private ?\SplObjectStorage $storage = null;
    private array $paths = [];

    public function __construct()
    {
        $this->clean();
    }

    public function getIterator(): \SplObjectStorage
    {
        return $this->storage;
    }

    public function has(object $entity): bool
    {
        return $this->storage->offsetExists($entity);
    }

    public function get(object $entity): ?Node
    {
        try {
            return $this->storage->offsetGet($entity);
        } catch (\UnexpectedValueException) {
            return null;
        }
    }

    public function find(string $role, array $scope): ?object
    {
        if (!\array_key_exists($role, $this->paths) || $this->paths[$role] === []) {
            return null;
        }

        $isComposite = false;
        switch (\count($scope)) {
            case 0:
                return null;
            case 1:
                $indexName = \key($scope);
                break;
            default:
                $isComposite = true;
                $indexName = \implode(self::INDEX_KEY_SEPARATOR, \array_keys($scope));
        }

        if (!$isComposite) {
            $value = (string) \current($scope);
            return $this->paths[$role][$indexName][$value] ?? null;
        }
        $result = null;
        // Find index
        if (!\array_key_exists($indexName, $this->paths[$role])) {
            $scopeKeys = \array_keys($scope);
            $scopeCount = \count($scopeKeys);
            foreach ($this->paths[$role] as $indexName => $values) {
                $indexKeys = \explode(self::INDEX_KEY_SEPARATOR, $indexName);
                $keysCount = \count($indexKeys);
                if ($keysCount <= $scopeCount && \count(\array_intersect($indexKeys, $scopeKeys)) === $keysCount) {
                    $result = &$this->paths[$role][$indexName];
                    break;
                }
            }
            // Index not found
            if ($result === null) {
                return null;
            }
        } else {
            $result = &$this->paths[$role][$indexName];
        }
        $indexKeys ??= \explode(self::INDEX_KEY_SEPARATOR, $indexName);
        foreach ($indexKeys as $key) {
            $value = (string) $scope[$key];
            if (!isset($result[$value])) {
                return null;
            }
            $result = &$result[$value];
        }
        return $result;
    }

    public function attach(object $entity, Node $node, array $index = []): void
    {
        $this->storage->offsetSet($entity, $node);
        $role = $node->getRole();

        if ($node->hasState()) {
            $this->eraseIndexes($role, $node->getData(), $entity);
            $data = $node->getState()->getData();
        } else {
            $data = $node->getData();
        }

        if ($data === []) {
            return;
        }
        foreach ($index as $key) {
            $isComposite = false;
            if (\is_array($key)) {
                switch (\count($key)) {
                    case 0:
                        continue 2;
                    case 1:
                        $indexName = \current($key);
                        break;
                    default:
                        $isComposite = true;
                        $indexName = \implode(self::INDEX_KEY_SEPARATOR, $key);
                }
            } else {
                $indexName = $key;
            }

            $rolePath = &$this->paths[$role][$indexName];

            // composite key
            if ($isComposite) {
                foreach ($key as $k) {
                    if (!isset($data[$k])) {
                        continue 2;
                    }
                    $value = (string) $data[$k];
                    $rolePath = &$rolePath[$value];
                }
                $rolePath = $entity;
            } else {
                if (!isset($data[$indexName])) {
                    continue;
                }
                $value = (string) $data[$indexName];
                $rolePath[$value] = $entity;
            }
        }
    }

    public function detach(object $entity): void
    {
        $node = $this->get($entity);
        if ($node === null) {
            return;
        }

        $role = $node->getRole();

        // erase all the indexes
        $this->eraseIndexes($role, $node->getData(), $entity);
        if ($node->hasState()) {
            $this->eraseIndexes($role, $node->getState()->getData(), $entity);
        }

        $this->storage->offsetUnset($entity);
    }

    public function clean(): void
    {
        $this->paths = [];
        $this->storage = new \SplObjectStorage();
    }

    public function __clone()
    {
        $this->storage = clone $this->storage;
    }

    public function __destruct()
    {
        $this->clean();
    }

    private function eraseIndexes(string $role, array $data, object $entity): void
    {
        if (!isset($this->paths[$role]) || empty($data)) {
            return;
        }
        foreach ($this->paths[$role] as $index => &$values) {
            if (empty($values)) {
                continue;
            }
            $keys = \explode(self::INDEX_KEY_SEPARATOR, $index);
            $j = \count($keys) - 1;
            $next = &$values;
            $removeFrom = &$next;
            // Walk index
            foreach ($keys as $i => $key) {
                $value = isset($data[$key]) ? (string) $data[$key] : null;
                if ($value === null || !isset($next[$value])) {
                    continue 2;
                }
                $removeKey ??= $value;
                // If last key
                if ($i === $j) {
                    if ($next[$value] === $entity) {
                        unset($removeFrom[$removeKey ?? $value]);
                    }
                    break;
                }
                // Optimization to remove empty arrays
                if (\count($next[$value]) > 1) {
                    $removeFrom = &$next[$value];
                    $removeKey = null;
                }
                $next = &$next[$value];
            }
        }
    }
}
