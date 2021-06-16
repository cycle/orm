<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Relation;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use JetBrains\PhpStorm\ExpectedValues;

class ShadowBelongsTo implements ReversedRelationInterface, DependencyInterface
{
    private string $name;
    private string $target;
    private array $schema;

    private array $outerKeys;
    private array $innerKeys;
    private bool $cascade;
    public function __construct(string $role, string $target, array $schema)
    {
        $this->name = $role . ':' . $target;
        $this->target = $role;
        $this->schema = $schema;
        $this->innerKeys = (array)($schema[Relation::SCHEMA][Relation::OUTER_KEY] ?? []);
        $this->outerKeys = (array)($schema[Relation::SCHEMA][Relation::INNER_KEY] ?? []);
        $this->cascade = (bool)($schema[Relation::SCHEMA][Relation::CASCADE] ?? false);
    }

    public function getInnerKeys(): array
    {
        return $this->innerKeys;
    }


    public function prepare(Pool $pool, Tuple $tuple, bool $load = true): void
    {
        $tuple->node->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        $status = $tuple->node->getRelationStatus($this->getName());
        if ($status === RelationInterface::STATUS_PREPARE && $this->isNullable()) {
            $tuple->node->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
        }
        if ($tuple->status >= Tuple::STATUS_WAITED) {
            // Check fields
            if ($this->isNullable() || $this->checkFieldsExists($tuple->state)) {
                $tuple->node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function getTarget(): string
    {
        return $this->target;
    }
    public function isCascade(): bool
    {
        return $this->cascade;
    }
    public function init(Node $node, array $data): array
    {
        return [];
    }
    public function extract($data)
    {
        return is_array($data) ? $data : [];
    }
    public function initPromise(Node $node): array
    {
        $scope = [];
        foreach ($this->innerKeys as $i => $key) {
            $innerValue = $this->fetchKey($parentNode, $key);
            if (empty($innerValue)) {
                return [null, null];
            }
            $scope[$this->outerKeys[$i]] = $innerValue;
        }

        $r = $this->orm->promise($this->target, $scope);

        return [$r, $r];
    }

    public function isNullable(): bool
    {
        return (bool)($this->schema[Relation::SCHEMA][Relation::NULLABLE] ?? false);
    }
    private function checkFieldsExists(State $state): bool
    {
        $data = $state->getData();
        foreach ($this->innerKeys as $key) {
            if (!isset($data[$key])) {
                return false;
            }
        }
        return true;
    }
}
