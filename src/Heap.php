<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

class Heap implements HeapInterface, \IteratorAggregate
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
    public function find(string $role, string $key, $value)
    {
        return $this->paths[$role][$key][$value] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function attach($entity, Node $node, array $index = [])
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
    public function detach($entity)
    {
        if (!$this->has($entity)) {
            return;
        }

        $node = $this->get($entity);

        // erase all the indexes
        if (isset($this->paths[$node->getRole()])) {
            $keys = array_keys($this->paths[$node->getRole()]);
            foreach ($keys as $key) {
                unset($this->paths[$node->getRole()][$key][$node->getData()[$key]]);
            }
        }

        // todo: DEPRECATE
        $this->paths = array_filter($this->paths, function ($value) use ($entity) {
            return $value !== $entity;
        });

        $this->storage->offsetUnset($entity);
    }

    public function hasPath(string $path)
    {
        // todo: this is fun, optimization is required
        return isset($this->paths[$path]);
    }

    // todo: this is fun
    public function getPath(string $path)
    {
        return $this->paths[$path];
    }

    /**
     * @inheritdoc
     */
    public function clean()
    {
        $this->paths = [];
        $this->storage = new \SplObjectStorage();
    }

    /**
     * Heap destructor.
     */
    public function __destruct()
    {
        $this->clean();
    }
}