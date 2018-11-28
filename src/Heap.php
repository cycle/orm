<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

class Heap implements HeapInterface
{
    /** @var \SplObjectStorage */
    private $storage;

    /** @var \SplObjectStorage */
    private $handlers;

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

    public function onChange($entity, callable $handler)
    {
        if ($this->has($entity)) {
            $this->get($entity)->attachListener($handler);

            return;
        }

        if ($this->handlers->offsetExists($entity)) {
            $this->handlers[$entity][] = $handler;
        } else {
            $this->handlers[$entity] = [$handler];
        }
    }

    /**
     * @inheritdoc
     */
    public function attach($entity, State $state, array $paths = [])
    {
        $this->storage->offsetSet($entity, $state);

        if ($this->handlers->offsetExists($entity)) {
            foreach ($this->handlers[$entity] as $handler) {
                $state->attachListener($handler);
            }

            $this->handlers->offsetUnset($entity);
        }

        foreach ($paths as $path) {
            $this->path[$path] = $entity;
        }
    }

    /**
     * @inheritdoc
     */
    public function detach($entity)
    {
        $this->storage->offsetUnset($entity);
        $this->handlers->offsetUnset($entity);

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
        $this->handlers = new \SplObjectStorage();
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