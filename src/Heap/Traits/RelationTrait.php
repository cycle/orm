<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

use Cycle\ORM\Relation\RelationInterface;
use JetBrains\PhpStorm\ExpectedValues;

trait RelationTrait
{
    private array $relations = [];

    /** @var array<string, int> */
    private array $relationStatus = [];

    public function setRelationStatus(
        string $name,
        #[ExpectedValues(valuesFromClass: RelationInterface::class)]
        int $status
    ): void {
        $this->relationStatus[$name] = $status;
        if ($status === RelationInterface::STATUS_RESOLVED) {
            \Cycle\ORM\Transaction\Pool::DEBUG && print "[RESOLVED] Relation {$this->getRole()}.$name\n";
        }
    }

    #[ExpectedValues(valuesFromClass: RelationInterface::class)]
    public function getRelationStatus(string $name): int
    {
        return $this->relationStatus[$name] ?? RelationInterface::STATUS_PREPARE;
    }

    public function setRelation(string $name, mixed $context): void
    {
        $this->relations[$name] = $context;
        unset($this->data[$name]);
    }

    public function hasRelation(string $name): bool
    {
        return array_key_exists($name, $this->relations);
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
