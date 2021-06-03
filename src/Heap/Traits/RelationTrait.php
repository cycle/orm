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

    // private array $resolvedRelations = [];
    //
    // public function isRelationResolved(string $name): bool
    // {
    //     return $this->resolvedRelations[$name] ?? false;
    // }

    public function setRelationStatus(
        string $name,
        #[ExpectedValues(values: [RelationInterface::STATUS_PREPARE, RelationInterface::STATUS_DEFERRED, RelationInterface::STATUS_RESOLVED])]
        int $status
    ): void {
        $this->relationStatus[$name] = $status;
        if ($status === RelationInterface::STATUS_RESOLVED) {
            \Cycle\ORM\Transaction\Pool::DEBUG AND print "[RESOLVED] Relation {$this->getRole()}.$name\n";
        }
    }

    #[ExpectedValues(values: [RelationInterface::STATUS_PREPARE, RelationInterface::STATUS_DEFERRED, RelationInterface::STATUS_RESOLVED])]
    public function getRelationStatus(string $name): ?int
    {
        return $this->relationStatus[$name] ?? null;
    }

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
