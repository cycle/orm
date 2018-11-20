<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

/**
 * State carries meta information about all load entities, including original set of data,
 * relations, state, primary key value (you can handle entities without PK included), and number of
 * active references (in cases when entity become unclaimed).
 */
final class State
{
    // Different entity states in a pool
    public const NEW              = 0;
    public const LOADED           = 1;
    public const SCHEDULED_INSERT = 2;
    public const SCHEDULED_UPDATE = 3;
    public const SCHEDULED_DELETE = 4;

    /** @var mixed */
    private $primaryKey;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    private $refCount = 1;

    private $relations = [];

    /**
     * @param mixed $primaryKey
     * @param int   $state
     * @param array $data
     */
    public function __construct($primaryKey, int $state, array $data)
    {
        $this->primaryKey = $primaryKey;
        $this->state = $state;
        $this->data = $data;
    }

    public function setPrimaryKey(string $name, $value)
    {
        $this->primaryKey = $value;
        $this->data[$name] = $value;

        foreach ($this->handlers as $handler) {
            call_user_func($handler, $this);
        }
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param int $state
     */
    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    public function setData(array $data)
    {
        $this->data = array_merge($data, $this->data);

        foreach ($this->handlers as $handler) {
            call_user_func($handler, $this);
        }
    }

    public function getKey(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    // todo: store original set of relations (YEEEEAH BOYYYY)
    public function setRelation(string $name, $context)
    {
        $this->relations[$name] = $context;
        unset($this->data[$name]);
    }

    public function getRelation(string $name)
    {
        return $this->relations[$name] ?? null;
    }

    public function getRelations(): array
    {
        return array_keys($this->relations);
    }

    /**
     * @return int
     */
    public function getRefCount(): int
    {
        return $this->refCount;
    }


    public function addReference()
    {
        $this->refCount++;
    }

    public function decReference()
    {
        $this->refCount--;
    }

    public function hasReferences(): bool
    {
        return $this->refCount > 0;
    }

    /**
     * @invisible
     * @var array
     */
    private $handlers = [];

    public function onUpdate(callable $handler)
    {
        $this->handlers[] = $handler;
    }

    private $refMap = [];

    public function getRefMap($rel)
    {
        return $this->refMap[$rel] ?? null;
    }

    public function setRefMap($rel, $id)
    {
        $this->refMap[$rel] = $id;
    }

    public function __destruct()
    {
        $this->data = [];
        $this->handlers = [];
        $this->relations = [];
    }
}