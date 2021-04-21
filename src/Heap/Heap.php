<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Heap;

use IteratorAggregate;
use SplObjectStorage;
use UnexpectedValueException;

final class Heap implements HeapInterface, IteratorAggregate
{
    private const KEY_SEPARATOR = ':';
    private const VALUE_SEPARATOR = '/';

    /** @var SplObjectStorage */
    private $storage;

    /** @var array */
    public $paths = [];

    /**
     * Heap constructor.
     */
    public function __construct()
    {
        $this->clean();
    }

    /**
     * Heap destructor.
     */
    public function __destruct()
    {
        $this->clean();
    }

    /**
     * @return SplObjectStorage
     */
    public function getIterator(): SplObjectStorage
    {
        return $this->storage;
    }

    /**
     * @inheritdoc
     */
    public function has($entity): bool
    {
        return $this->storage->offsetExists($entity);
    }

    /**
     * @inheritdoc
     */
    public function get($entity): ?Node
    {
        try {
            return $this->storage->offsetGet($entity);
        } catch (UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function find(string $role, array $scope)
    {
        if (count($scope) === 1) {
            $key = key($scope);
            if (is_object($scope[$key])) {
                $scope[$key] = (string)$scope[$key];
            }
            return $this->paths[$role][$key][$scope[$key]] ?? null;
        }

        $key = implode(self::KEY_SEPARATOR, array_keys($scope));
        $value = $this->escapeValues($scope);

        return $this->paths[$role][$key][$value] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function attach(object $entity, Node $node, array $index = []): void
    {
        $this->storage->offsetSet($entity, $node);

        $data = $node->getData();
        foreach ($index as $key) {
            if (is_array($key)) {
                $values = [];
                foreach ($key as $k) {
                    $values[] = (string) ($data[$k] ?? "\0");
                }
                $value = $this->escapeValues($values);
                $key = implode(self::KEY_SEPARATOR, $key);
            } else {
                if (!isset($data[$key])) {
                    continue;
                }

                $value = (string)$data[$key];
            }

            $this->paths[get_class($entity)][$key][$value] = $entity;
            $this->paths[$node->getRole()][$key][$value] = $entity;
        }
    }

    /**
     * @inheritdoc
     */
    public function detach($entity): void
    {
        $node = $this->get($entity);
        if ($node === null) {
            return;
        }

        $role = $node->getRole();

        // erase all the indexes
        if (isset($this->paths[$role])) {
            $keys = array_keys($this->paths[$role]);
            foreach ($keys as $key) {
                unset($this->paths[$role][$key][$this->extractNodeValueByKey($node, $key)]);
            }
        }

        $this->storage->offsetUnset($entity);
    }

    /**
     * @inheritdoc
     */
    public function clean(): void
    {
        $this->paths = [];
        $this->storage = new \SplObjectStorage();
    }

    private function extractNodeValueByKey(Node $node, string $key): string
    {
        $composite = strpos($key, self::KEY_SEPARATOR) !== false;

        if (!$composite) {
            return (string) $node->getData()[$key];
        }

        $data = [];
        foreach (explode(self::KEY_SEPARATOR, $key) as $k) {
            $data[] = $node->getData()[$k];
        }
        return $this->escapeValues($data);
    }

    private function escapeValues(array $values): string
    {
        $result = '';
        foreach ($values as $value) {
            $result .= str_replace(
                    self::VALUE_SEPARATOR,
                    self::VALUE_SEPARATOR . self::VALUE_SEPARATOR,
                    (string) $value
                ) . self::VALUE_SEPARATOR;
        }
        return $result;
    }
}
