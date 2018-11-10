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
    private const ID     = 0;
    private const DATA   = 1;
    private const STATE  = 2;
    private const RELMAP = 3;

    /** @var \SplObjectStorage */
    private $storage;

    /** @var object[] */
    private $hashmap;

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
    public function has(string $class, $entityID): bool
    {
        return isset($this->hashmap["{$class}:{$entityID}"]);
    }

    /**
     * @inheritdoc
     */
    public function hasInstance($entity): bool
    {
        return $this->storage->offsetExists($entity);
    }

    /**
     * @inheritdoc
     */
    public function get(string $class, $entityID)
    {
        return $this->hashmap["{$class}:{$entityID}"] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function register(
        $entity,
        $entityID,
        array $data,
        int $state,
        RelationMap $relmap = null
    ) {
        $this->storage->attach($entity, [$entityID, $data, $state, $relmap]);

        $class = get_class($entity);
        $this->hashmap["{$class}:{$entityID}"] = $entity;
    }

    /**
     * @inheritdoc
     */
    public function remove(string $class, $entityID)
    {
        $entity = $this->get($class, $entityID);
        if ($entity === null) {
            return;
        }

        unset($this->hashmap["{$class}:{$entityID}"]);
        $this->storage->detach($entity);
    }

    /**
     * @inheritdoc
     */
    public function removeInstance($entity)
    {
        if (!$this->hasInstance($entity)) {
            return;
        }

        $class = get_class($entity);
        $entityID = $this->fetchValue($entity, self::ID);

        unset($this->hashmap["{$class}:{$entityID}"]);
        $this->storage->detach($entity);
    }

    /**
     * @inheritdoc
     */
    public function setData($entity, array $data)
    {
        $this->updateValue($entity, self::DATA, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData($entity): array
    {
        return $this->fetchValue($entity, self::DATA);
    }

    /**
     * @inheritdoc
     */
    public function setState($entity, int $state)
    {
        $this->updateValue($entity, self::STATE, $state);
    }

    /**
     * @inheritdoc
     */
    public function getState($entity): int
    {
        return $this->fetchValue($entity, self::STATE);
    }

    /**
     * @inheritdoc
     */
    public function getRelationMap($entity): ?RelationMap
    {
        return $this->fetchValue($entity, self::RELMAP);
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->storage = new \SplObjectStorage();
        $this->hashmap = [];
    }

    /**
     * Heap destructor.
     */
    public function __destruct()
    {
        $this->reset();
    }

    private function fetchValue($entity, int $section)
    {
        // todo: handle exception
        return $this->storage->offsetGet($entity)[$section];
    }

    private function updateValue($entity, int $section, $value)
    {
        $data = $this->storage->offsetGet($entity);
        $this->storage->offsetSet($entity, [$section => $value] + $data);
    }
}