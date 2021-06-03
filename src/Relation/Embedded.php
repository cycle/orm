<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Embeds one object to another.
 */
final class Embedded implements SameRowRelationInterface
{
    use Relation\Traits\NodeTrait;

    /** @internal */
    private ORMInterface $orm;

    private string $name;

    private string $target;

    private MapperInterface $mapper;

    /** @var string[] */
    private array $primaryKeys;

    private array $columns;

    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        $this->orm = $orm;
        $this->name = $name;
        $this->target = $target;
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

    public function init(Node $node, array $data): array
    {
        foreach ($this->primaryKeys as $key) {
            // ensure proper object reference
            $data[$key] = $node->getData()[$key];
        }

        $item = $this->orm->make($this->target, $data, Node::MANAGED);

        return [$item, $item];
    }

    public function initPromise(Node $parentNode): array
    {
        $values = [];
        foreach ($this->primaryKeys as $key) {
            $value = $this->fetchKey($parentNode, $key);
            if (empty($value)) {
                return [null, null];
            }
            $values[] = $value;
        }

        /** @var ORMInterface $orm */
        $orm = $this->orm;

        $pk = array_combine($this->primaryKeys, $values);
        $e = $orm->getHeap()->find($this->target, $pk);
        if ($e !== null) {
            return [$e, $e];
        }

        $r = $this->orm->promise($this->target, $pk);
        return [$r, $pk];
    }

    public function extract($data)
    {
        return $data;
    }

    public function newQueue(Pool $pool, Tuple $tuple, $related, CommandInterface $command = null): void
    {
        if ($tuple->task !== Tuple::TASK_STORE) {
            return;
        }

        // Master Node
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());

        if ($related instanceof ReferenceInterface) {
            if ($related->__scope() === $original) {
                if (!($related instanceof PromiseInterface && $related->__loaded())) {
                    // do not update non resolved and non changed promises
                    return;
                }

                $related = $this->resolve($related);
            } else {
                // do not affect parent embeddings
                $related = clone $this->resolve($related);
            }
        }

        if ($related === null) {
            throw new NullException("Embedded relation `{$this->name}` can't be null.");
        }

        $rNode = $this->getNode($related);
        // calculate embedded node changes
        $changes = $this->getChanges($related, $rNode->getState());
        foreach ($this->primaryKeys as $key) {
            if (isset($changes[$key])) {
                $rNode->register($key, $changes[$key]);
            }
        }

        $mapper = $this->orm->getMapper($this->getTarget());
        $rNode = $this->getNode($related);
        $changes = $this->getChanges($related, $rNode->getState());
        foreach ($mapper->mapColumns($changes) as $field => $value) {
            $command->registerAppendix($field, $value);
        }
        $rNode->getState()->setStatus(Node::MANAGED);
        $rNode->getState()->setTransactionData($changes);

        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        $node->setRelationStatus($rNode->getRole() . ':' . $this->getName(), RelationInterface::STATUS_RESOLVED);
    }


    public function queue(object $entity, Node $node, $related, $original): CommandInterface
    {
        if ($related instanceof ReferenceInterface) {
            if ($related->__scope() === $original) {
                if (!($related instanceof PromiseInterface && $related->__loaded())) {
                    // do not update non resolved and non changed promises
                    return new Nil();
                }

                $related = $this->resolve($related);
            } else {
                // do not affect parent embeddings
                $related = clone $this->resolve($related);
            }
        }

        if ($related === null) {
            throw new NullException("Embedded relation `{$this->name}` can't be null.");
        }

        $state = $this->getNode($related)->getState();

        // calculate embedded node changes
        $changes = $this->getChanges($related, $state);

        // register node changes
        $state->setData($changes);

        // store embedded entity changes via parent command
        foreach ($this->mapColumns($changes) as $key => $value) {
            $store->register($key, $value, true);
        }

        // currently embeddings does not support chain relations, however it is possible by
        // exposing relationMap inside this method. in theory it is possible to use
        // parent entity command to carry context for nested relations, however, custom context
        // propagation chain must be defined (embedded node => parent command)
        // in short, we need to get access to getRelationMap from orm to support it.

        return new Nil();
    }

    private function getChanges(object $related, State $state): array
    {
        $data = array_intersect_key($this->mapper->extract($related), $this->columns);
        // Embedded entity does not override PK values of the parent
        foreach ($this->primaryKeys as $key) {
            unset($data[$key]);
        }

        return array_udiff_assoc($data, $state->getTransactionData(), [static::class, 'compare']);
    }

    /**
     * Map internal field names to database specific column names.
     */
    private function mapColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $column => $value) {
            if (array_key_exists($column, $this->columns)) {
                $result[$this->columns[$column]] = $value;
            } else {
                $result[$column] = $value;
            }
        }

        return $result;
    }

    /**
     * @param mixed $a
     * @param mixed $b
     */
    private static function compare($a, $b): int
    {
        return $a <=> $b;
    }

    /**
     * Resolve the reference to the object.
     *
     * @return mixed|null
     */
    private function resolve(ReferenceInterface $reference)
    {
        if ($reference instanceof PromiseInterface) {
            return $reference->__resolve();
        }

        return $this->orm->get($reference->__role(), $reference->__scope(), true);
    }

    /**
     * Fetch key from the state.
     *
     * @return mixed|null
     */
    private function fetchKey(?Node $state, string $key)
    {
        if ($state === null) {
            return null;
        }

        return $state->getData()[$key] ?? null;
    }
}
