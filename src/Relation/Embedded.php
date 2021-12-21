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
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Embeds one object to another.
 *
 * @internal
 */
final class Embedded implements SameRowRelationInterface
{
    private MapperInterface $mapper;
    private MapperProviderInterface $mapperProvider;
    private EntityProviderInterface $entityProvider;

    /** @var string[] */
    private array $primaryKeys;

    private array $columns;

    public function __construct(
        /** @internal */
        ORMInterface $orm,
        private string $name,
        private string $target
    ) {
        $this->mapperProvider = $orm->getService(MapperProviderInterface::class);
        $this->entityProvider = $orm->getService(EntityProviderInterface::class);
        $this->mapper = $this->mapperProvider->getMapper($target);

        // this relation must manage column association manually, bypassing related mapper
        $this->primaryKeys = (array)$orm->getSchema()->define($target, SchemaInterface::PRIMARY_KEY);
        $this->columns = $orm->getSchema()->define($target, SchemaInterface::COLUMNS);
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

    public function init(EntityFactoryInterface $factory, Node $node, array $data): object
    {
        foreach ($this->primaryKeys as $key) {
            // ensure proper object reference
            $data[$key] = $node->getData()[$key];
        }

        $item = $factory->make($this->target, $data, Node::MANAGED);
        $node->setRelation($this->getName(), $item);

        return $item;
    }

    public function cast(?array $data): ?array
    {
        return $data === null
            ? null
            : $this->mapperProvider->getMapper($this->target)->cast($data);
    }

    public function collect(mixed $data): ?object
    {
        \assert($data === null || \is_object($data));
        return $data;
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
                $rTuple->state->register($key, $changes[$key]);
            }
        }

        if ($command !== null) {
            $mapper = $this->mapperProvider->getMapper($this->target);
            $changes = $mapper->uncast($changes);
            foreach ($mapper->mapColumns($changes) as $field => $value) {
                $command->registerColumn($field, $value);
            }
        }
        $rTuple->state->setStatus(Node::MANAGED);
        $rTuple->state->updateTransactionData();

        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        $tuple->state->setRelationStatus($rTuple->node->getRole() . ':' . $this->getName(), RelationInterface::STATUS_RESOLVED);
    }

    private function getChanges(object $related, State $state): array
    {
        $data = array_intersect_key($this->mapper->extract($related), $this->columns);
        // Embedded entity does not override PK values of the parent
        foreach ($this->primaryKeys as $key) {
            unset($data[$key]);
        }

        return array_udiff_assoc($data, $state->getTransactionData(), [Node::class, 'compare']);
    }

    /**
     * Resolve the reference to the object.
     */
    public function resolve(ReferenceInterface $reference, bool $load): ?object
    {
        if ($reference->hasValue()) {
            return $reference->getValue();
        }

        $result = $this->entityProvider->get($reference->getRole(), $reference->getScope(), $load);
        if ($load === true || $result !== null) {
            $reference->setValue($result);
        }
        return $result;
    }
}
