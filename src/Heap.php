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

    /**
     * @inheritdoc
     */
    public function attach($entity, State $state)
    {
        $this->storage->offsetSet($entity, $state);
        if (!empty($state->getPrimaryKey())) {
            $this->path[get_class($entity) . ':' . $state->getPrimaryKey()] = $entity;
        }
    }

    /**
     * @inheritdoc
     */
    public function detach($entity)
    {
        $this->storage->offsetUnset($entity);
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->storage = new \SplObjectStorage();
    }

    public function hasPath(string $class, $entityID)
    {
        return isset($this->path["{$class}:{$entityID}"]);
    }

    public function getPath(string $class, $entityID)
    {
        return $this->path["{$class}:{$entityID}"];
    }

    /**
     * Heap destructor.
     */
    public function __destruct()
    {
        $this->reset();
    }
}