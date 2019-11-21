<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

trait RelationTrait
{
    /** @var array */
    private $relations = [];

    /**
     * @param string $name
     * @param mixed  $context
     */
    public function setRelation(string $name, $context): void
    {
        $this->relations[$name] = $context;
        unset($this->data[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasRelation(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getRelation(string $name)
    {
        return $this->relations[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }
}
