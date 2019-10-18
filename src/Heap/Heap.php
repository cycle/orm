<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Heap;

final class Heap implements HeapInterface, \IteratorAggregate
{
    /** @var \SplObjectStorage */
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
     * @return \SplObjectStorage
     */
    public function getIterator(): \SplObjectStorage
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
        } catch (\UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function find(string $role, array $scope)
    {
        foreach ($scope as $key => $value) {
            // first match for now
            return $this->paths[$role][$key][$value] ?? null;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function attach($entity, Node $node, array $index = []): void
    {
        $this->storage->offsetSet($entity, $node);

        foreach ($index as $key) {
            if (!isset($node->getData()[$key])) {
                continue;
            }

            $this->paths[get_class($entity)][$key][$node->getData()[$key]] = $entity;
            $this->paths[$node->getRole()][$key][$node->getData()[$key]] = $entity;
        }
    }

    /**
     * @inheritdoc
     */
    public function detach($entity): void
    {
        if (!$this->has($entity)) {
            return;
        }

        $node = $this->get($entity);
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
