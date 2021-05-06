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
    /** @var SplObjectStorage */
    private $storage;

    /** @var array */
    private $paths = [];

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

    public function getIterator(): SplObjectStorage
    {
        return clone $this->storage;
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

        $key = $value = '';
        foreach ($scope as $k => $v) {
            $key .= $k;
            $value .= $v . '/';
        }

        return $this->paths[$role][$key][$value] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function attach($entity, Node $node, array $index = []): void
    {
        $this->storage->offsetSet($entity, $node);
        $role = $node->getRole();

        if ($node->hasState()) {
            $this->eraseIndexes($role, $node->getInitialData(), $entity);
        }

        $data = $node->getData();
        foreach ($index as $key) {
            if (is_array($key)) {
                $keyName = $value = '';
                foreach ($key as $k) {
                    $keyName .= $k; // chance of collision?
                    $value .= (string)$data[$k] . '/';
                }
                $key = $keyName;
            } else {
                if (!isset($data[$key])) {
                    continue;
                }

                $value = (string)$data[$key];
            }

            $this->paths[$role][$key][$value] = $entity;
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

        $this->eraseIndexes($role, $node->getData(), $entity);
        if ($node->hasState()) {
            $this->eraseIndexes($role, $node->getInitialData(), $entity);
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

    private function eraseIndexes(string $role, array $data, object $entity): void
    {
        if (!isset($this->paths[$role])) {
            return;
        }
        $keys = array_keys($this->paths[$role]);
        foreach ($keys as $key) {
            $value = isset($data[$key]) ? (string)$data[$key] : null;
            if ($value === null) {
                continue;
            }
            $current = &$this->paths[$role][$key];
            if (isset($current[$value]) && $current[$value] === $entity) {
                unset($current[$value]);
            }
        }
    }
}
