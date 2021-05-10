<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

trait RelationTrait
{
    private array $relations = [];

    /**
     * @param mixed  $context
     */
    public function setRelation(string $name, $context): void
    {
        $this->relations[$name] = $context;
        unset($this->data[$name]);
    }

    public function hasRelation(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    /**
     * @return mixed
     */
    public function getRelation(string $name)
    {
        return $this->relations[$name] ?? null;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }
}
