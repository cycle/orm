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

    public function onUpdate($entity, callable $handler)
    {
        if (!$this->has($entity)) {
            if ($this->handlers->offsetExists($entity)) {
                $this->handlers->offsetSet(
                    $entity,
                    array_merge($this->handlers->offsetGet($entity), [$handler])
                );
            } else {
                $this->handlers->offsetSet($entity, [$handler]);
            }

        } else {
            $this->get($entity)->onUpdate($handler);
        }
    }

    /**
     * @inheritdoc
     */
    public function attach($entity, State $state)
    {
        $this->storage->offsetSet($entity, $state);
        if ($this->handlers->offsetExists($entity)) {
            foreach ($this->handlers->offsetGet($entity) as $handler) {
                $state->onUpdate($handler);
            }
            $this->handlers->offsetUnset($entity);
        }

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
        $this->handlers = new \SplObjectStorage();
    }

    public function hasPath(string $class, $entityID)
    {
        // todo: this is fun
        return isset($this->path["{$class}:{$entityID}"]);
    }

    // todo: this is fun
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