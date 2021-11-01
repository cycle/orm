<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\EmptyReference;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Embeds one object to another.
 */
final class Embedded implements SameRowRelationInterface
{
    private MapperInterface $mapper;

    /** @var string[] */
    private array $primaryKeys;

    private array $columns;

    public function __construct(
        /** @internal */
        private ORMInterface $orm,
        private string $name,
        private string $target
    ) {
        $this->mapper = $this->orm->getMapper($target);

        // this relation must manage column association manually, bypassing related mapper
        $this->primaryKeys = (array)$this->orm->getSchema()->define($target, Schema::PRIMARY_KEY);
        $this->columns = $this->orm->getSchema()->define($target, Schema::COLUMNS);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInnerKeys(): array
    {
        return $this->primaryKeys;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function isCascade(): bool
    {
        // always cascade
        return true;
    }

    public function init(Node $node, array $data, bool $typecast = false): object
    {
        foreach ($this->primaryKeys as $key) {
            // ensure proper object reference
            $data[$key] = $node->getData()[$key];
        }

        $item = $this->orm->make($this->target, $data, Node::MANAGED);
        $node->setRelation($this->getName(), $item);

        return $item;
    }

    public function cast(?array $data): ?array
    {
        return $data === null
            ? null
            : ($this->orm->getEntityRegistry()->getMapper($this->target)?->cast($data) ?? $data);
    }

    public function collect($source): ?object
    {
        return $source;
    }

    public function initReference(Node $node): ReferenceInterface
    {
        $scope = $this->getReferenceScope($node);
        if ($scope === null) {
            $result = new Reference($this->target, []);
            $result->setValue(null);
            return $result;
        }
        return $scope === [] ? new EmptyReference($this->target, null) : new Reference($this->target, $scope);
    }

    private function getReferenceScope(Node $node): ?array
    {
        $scope = [];
        $nodeData = $node->getData();
        foreach ($this->primaryKeys as $key) {
            $value = $nodeData[$key] ?? null;
            if (empty($value)) {
                return null;
            }
            $scope[$key] = $value;
        }
        return $scope;
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        // $related = $tuple->state->getRelation($this->getName());
        // $pool->attach($related, Tuple::TASK_STORE, false);
    }

    public function queue(Pool $pool, Tuple $tuple, StoreCommandInterface $command = null): void
    {
        if ($tuple->task !== Tuple::TASK_STORE) {
            return;
        }
        $related = $tuple->state->getRelation($this->getName());

        // Master Node
        $original = $tuple->node->getRelation($this->getName());

        if ($related instanceof ReferenceInterface) {
            if ($related === $original) {
                if (!$related->hasValue() || $this->resolve($related, false) === null) {
                    // do not update non resolved and non changed promises
                    return;
                }
                $related = $related->getValue();
            } else {
                // do not affect parent embeddings
                $related = clone $this->resolve($related, true);
            }
        }

        if ($related === null) {
            throw new NullException("Embedded relation `{$this->name}` can't be null.");
        }
        $tuple->state->setRelation($this->getName(), $related);

        $rTuple = $pool->attach($related, Tuple::TASK_STORE, false);
        // calculate embedded node changes
        $changes = $this->getChanges($related, $rTuple->state);
        foreach ($this->primaryKeys as $key) {
            if (isset($changes[$key])) {
                $rTuple->node->register($key, $changes[$key]);
            }
        }

        $mapper = $this->orm->getMapper($this->target);
        $changes = $this->getChanges($related, $rTuple->state);
        if ($command !== null) {
            foreach ($mapper->mapColumns($changes) as $field => $value) {
                $command->registerColumn($field, $value);
            }
        }
        $rTuple->state->setStatus(Node::MANAGED);
        $rTuple->state->updateTransactionData();

        $tuple->node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        $tuple->node->setRelationStatus($rTuple->node->getRole() . ':' . $this->getName(), RelationInterface::STATUS_RESOLVED);
    }

    private function getChanges(object $related, State $state): array
    {
        $data = array_intersect_key($this->mapper->extract($related), $this->columns);
        // Embedded entity does not override PK values of the parent
        foreach ($this->primaryKeys as $key) {
            unset($data[$key]);
        }

        // todo use Node::compare?
        return array_udiff_assoc($data, $state->getTransactionData(), [self::class, 'compare']);
    }

    private static function compare(mixed $a, mixed $b): int
    {
        return $a <=> $b;
    }

    /**
     * Resolve the reference to the object.
     */
    public function resolve(ReferenceInterface $reference, bool $load): ?object
    {
        if ($reference->hasValue()) {
            return $reference->getValue();
        }

        $result = $this->orm->get($reference->getRole(), $reference->getScope(), $load);
        if ($load === true || $result !== null) {
            $reference->setValue($result);
        }
        return $result;
    }
}
