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

        $data = $node->getData();
        foreach ($index as $key) {
            if (is_array($key)) {
                $keyName = $value = '';
                foreach ($key as $k) {
                    $keyName .= $k; // chance of collision?
                    $value .= $data[$k] . '/';
                }
                $key = $keyName;
            } else {
                if (!isset($data[$key])) {
                    continue;
                }

                $value = $data[$key];
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
                unset($this->paths[$role][$key][$node->getData()[$key]]);
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
}
