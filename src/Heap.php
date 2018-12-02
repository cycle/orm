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
    private $path = [];

    /**
     * Heap constructor.
     */
    public function __construct()
    {
        $this->reset();
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
    public function get($entity): ?State
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
    public function attach($entity, State $state, array $index = [])
    {
        $this->storage->offsetSet($entity, $state);

        foreach ($index as $path) {
            $this->path[$path] = $entity;

            // todo: need better path approach
            $this->path[get_class($entity)][$path] = $entity;
        }
    }

    /**
     * @inheritdoc
     */
    public function detach($entity)
    {
        $this->storage->offsetUnset($entity);

        // rare usage
        $this->path = array_filter($this->path, function ($value) use ($entity) {
            return $value !== $entity;
        });
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->path = [];
        $this->storage = new \SplObjectStorage();
    }

    public function hasPath(string $path)
    {
        // todo: this is fun, optimization is required
        return isset($this->path[$path]);
    }

    // todo: this is fun
    public function getPath(string $path)
    {
        return $this->path[$path];
    }

    /**
     * Heap destructor.
     */
    public function __destruct()
    {
        $this->reset();
    }
}