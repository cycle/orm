<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\PromiseOne;
use Cycle\ORM\Promise\ReferenceInterface;
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


    public function newQueue(Pool $pool, Tuple $tuple, $related): void
    {
        ob_flush();
        $status = $tuple->node->getRelationStatus($this->getName());
        if ($status === RelationInterface::STATUS_PROCESSING && $this->isNullable()) {
            $tuple->node->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
        }
    }

    private function pullValues(Node $node, Node $related): void
    {
        $changes = $related->getState()->getTransactionData();
        foreach ($this->outerKeys as $i => $outerKey) {
            if (isset($changes[$outerKey])) {
                $node->register($this->innerKeys[$i], $changes[$outerKey]);
            }
        }
    }

    public function queue($entity, Node $node, $related, $original): CommandInterface
    {
        throw new \Exception();
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

    private function isNullable(): bool
    {
        return (bool)($this->schema[Relation::SCHEMA][Relation::NULLABLE] ?? false);
    }
}
