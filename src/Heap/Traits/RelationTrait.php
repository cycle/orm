<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

trait RelationTrait
{
    private array $relations = [];

    public function setRelation(string $name, mixed $context): void
    {
        $this->relations[$name] = $context;
        unset($this->data[$name]);
    }

    public function hasRelation(string $name): bool
    {
        return \array_key_exists($name, $this->relations);
    }

    public function getRelation(string $name): mixed
    {
        return $this->relations[$name] ?? null;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }
}
