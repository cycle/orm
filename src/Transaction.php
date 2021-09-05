<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\Special\Sequence;
use Cycle\ORM\Command\Special\WrappedStoreCommand;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Relation\ShadowBelongsTo;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Runner;
use Cycle\ORM\Transaction\RunnerInterface;
use Cycle\ORM\Transaction\Tuple;

/**
 * Transaction provides ability to define set of entities to be stored or deleted within one transaction. Transaction
 * can operate as UnitOfWork. Multiple transactions can co-exists in one application.
 *
 * Internally, upon "run", transaction will request mappers to generate graph of linked commands to create, update or
 * delete entities.
 */
final class Transaction implements TransactionInterface
{
    private const RELATIONS_NOT_RESOLVED = 0;
    private const RELATIONS_RESOLVED = 1;
    private const RELATIONS_DEFERRED = 2;

    private Pool $pool;

    private RunnerInterface $runner;

    private array $indexes = [];

    public function __construct(
        private ORMInterface $orm,
        RunnerInterface $runner = null
    ) {
        $this->runner = $runner ?? new Runner();
        $this->pool = new Pool($orm);
    }

    public function persist(object $entity, int $mode = self::MODE_CASCADE): self
    {
        $this->pool->attachStore($entity, $mode === self::MODE_CASCADE);
        return $this;
    }

    public function delete(object $entity, int $mode = self::MODE_CASCADE): self
    {
        $this->pool->attach($entity, Tuple::TASK_FORCE_DELETE, $mode === self::MODE_CASCADE);

        return $this;
    }

    public function run(): void
    {
        try {
            $this->walkPool();
        } catch (\Throwable $e) {
            $this->runner->rollback();

            // no calculations must be kept in node states, resetting
            // this will keep entity data as it was before transaction run
            $this->resetHeap();

            throw $e;
        } finally {
            if (!isset($e)) {
                // we are ready to commit all changes to our representation layer
                $this->syncHeap();
            }
        }

        $this->runner->complete();
    }

    private function runCommand(?CommandInterface $command): void
    {
        if ($command === null) {
            return;
        }
        $this->runner->run($command);
        $this->pool->someHappens();
    }

    /**
     * Sync all entity states with generated changes.
     */
    private function syncHeap(): void
    {
        $heap = $this->orm->getHeap();
        // $iterator = (clone $heap)->getIterator();
        // foreach ($iterator as $e) {
        $iterator = $heap->getIterator();
        $iterator->rewind();
        while ($iterator->valid()) {
            $e = $iterator->current();
            $iterator->next();
            // optimize to only scan over affected entities
            /** @var Node $node */
            $node = $heap->get($e);

            if (!$node->hasState()) {
                continue;
            }

            // marked as being deleted and has no external claims (GC like approach)
            if (in_array($node->getStatus(), [Node::DELETED, Node::SCHEDULED_DELETE], true)) {
                $heap->detach($e);
                continue;
            }

            // reindex the entity while it has old data
            $heap->attach($e, $node, $this->getIndexes($node->getRole()));

            // sync the current entity data with newly generated data
            $mapper = $this->orm->getMapper($node->getRole());
            // $entityRelations = $mapper->fetchRelations($e);
            $syncData = $node->syncState($this->orm->getRelationMap($node->getRole()));
            $mapper->hydrate($e, $syncData);
        }
    }

    /**
     * Reset heap to it's initial state and remove all the changes.
     */
    private function resetHeap(): void
    {
        $heap = $this->orm->getHeap();
        foreach ($heap as $e) {
            $heap->get($e)->resetState();
        }
    }

    /**
     * Return flattened list of commands required to store and delete associated entities.
     */
    private function walkPool(): void
    {
        /**
         * @var object $entity
         * @var Tuple $tuple
         */
        foreach ($this->pool as $entity => $tuple) {
            if ($entity instanceof ReferenceInterface) {
                if ($entity->hasValue()) {
                    $entity = $entity->getValue();
                    if ($entity === null) {
                        \Cycle\ORM\Transaction\Pool::DEBUG && print "pool: skip unresolved promise\n";
                        continue;
                    }
                    $tuple->entity = $entity;
                } else {
                    continue;
                }
            }

            if (!$tuple->node || !$tuple->state || !$tuple->mapper) {
                throw new \Exception();
            }
            // we do not expect to store promises
            if ($tuple->task === Tuple::TASK_FORCE_DELETE && $tuple->node === null) {
                $tuple->status = Tuple::STATUS_PROCESSED;
                continue;
            }
            \Cycle\ORM\Transaction\Pool::DEBUG && print sprintf(
                "\nPool: %s %s \033[35m%s(%s)\033[0m data: %s\n",
                ['preparing','waiting','waited','deferred','proposed','preprocessed','processed'][$tuple->status],
                ['store', 'delete', 'force delete'][$tuple->task],
                $tuple->node === null ? $entity::class : $tuple->node->getRole(),
                spl_object_id($entity),
                $tuple->node === null
                    ? '(has no Node)'
                    : implode('|', array_map(static fn ($x) => \is_object($x)
                        ? $x::class
                        : (string)$x, $tuple->node->getData()))
            );

            if ($tuple->task === Tuple::TASK_FORCE_DELETE && !$tuple->cascade) {
                // currently we rely on db to delete all nested records (or soft deletes)
                // todo delete cascaded
                $command = $this->generateDeleteCommand($tuple);
                $this->runCommand($command);
                $tuple->status = Tuple::STATUS_PROCESSED;
                continue;
            }
            // Walk relations
            $this->resolveRelations($tuple);
        }
    }

    private function resolveMasterRelations(Tuple $tuple, RelationMap $map): int
    {
        if (!$map->hasDependencies()) {
            return self::RELATIONS_RESOLVED;
        }

        $deferred = false;
        $resolved = true;
        $waitKeys = [];
        foreach ($map->getMasters() as $name => $relation) {
            $className = "\033[33m" . substr($relation::class, strrpos($relation::class, '\\') + 1) . "\033[0m";
            $role = $tuple->node->getRole();
            $relationStatus = $tuple->node->getRelationStatus($relation->getName());
            if (/*!$relation->isCascade() || */$relationStatus === RelationInterface::STATUS_RESOLVED) {
                \Cycle\ORM\Transaction\Pool::DEBUG && print "\033[32m  Master {$role}.{$name}\033[0m skip {$className}\n";
                continue;
            }

            if ($relation instanceof ShadowBelongsTo) {
                // Check relation is connected
                // Connected -> $parentNode->getRelationStatus()
                // Disconnected -> WAIT if Tuple::STATUS_PREPARING
                $relation->queue($this->pool, $tuple);
                $relationStatus = $tuple->node->getRelationStatus($relation->getName());

                // if ($tuple->status < Tuple::STATUS_PROPOSED) {
                $resolved = $resolved && $relationStatus >= RelationInterface::STATUS_DEFERRED;
                $deferred = $deferred || $relationStatus === RelationInterface::STATUS_DEFERRED;
            // }
            } else {
                if ($tuple->status === Tuple::STATUS_PREPARING) {
                    if ($relationStatus === RelationInterface::STATUS_PREPARE) {
                        $entityData ??= $tuple->mapper->fetchRelations($tuple->entity);
                        // $tuple->state->setRelation($name, $entityData[$name] ?? null);
                        $relation->prepare($this->pool, $tuple, $entityData[$name] ?? null);
                        $relationStatus = $tuple->node->getRelationStatus($relation->getName());
                    }
                } else {
                    $relation->queue($this->pool, $tuple);
                    $relationStatus = $tuple->node->getRelationStatus($relation->getName());
                }
                $resolved = $resolved && $relationStatus >= RelationInterface::STATUS_DEFERRED;
                $deferred = $deferred || $relationStatus === RelationInterface::STATUS_DEFERRED;
            }
            if ($relationStatus !== RelationInterface::STATUS_RESOLVED) {
                $unresdef = $relationStatus === RelationInterface::STATUS_DEFERRED ? 'deferred' : 'not resolved';
                \Cycle\ORM\Transaction\Pool::DEBUG && print "\033[34m  Master {$role}.{$name}\033[0m {$unresdef} {$relationStatus} {$className}\n";
            // $waitKeys[] = $relation->getInnerKeys();
            } else {
                \Cycle\ORM\Transaction\Pool::DEBUG && print "\033[32m  Master {$role}.{$name}\033[0m resolved {$className}\n";
            }
        }

        // $tuple->waitKeys = array_unique(array_merge(...$waitKeys));
        return ($deferred ? self::RELATIONS_DEFERRED : 0) | ($resolved ? self::RELATIONS_RESOLVED : 0);
    }

    private function resolveSlaveRelations(Tuple $tuple, RelationMap $map): int
    {
        if (!$map->hasSlaves()) {
            return self::RELATIONS_RESOLVED;
        }
        $changedFields = array_keys($tuple->state->getChanges());

        // Attach children to pool
        $transactData = $tuple->state->getTransactionData();
        $deferred = false;
        $resolved = true;
        if ($tuple->status === Tuple::STATUS_PREPARING) {
            // $entityData = $tuple->mapper->extract($tuple->entity);
            // $relData = $tuple->mapper->extract($tuple->entity);
            $relData = $tuple->mapper->fetchRelations($tuple->entity);
        }
        foreach ($map->getSlaves() as $name => $relation) {
            $relationStatus = $tuple->node->getRelationStatus($relation->getName());
            $className = "\033[33m" . substr($relation::class, strrpos($relation::class, '\\') + 1) . "\033[0m";
            $role = $tuple->node->getRole();
            if (!$relation->isCascade() || $relationStatus === RelationInterface::STATUS_RESOLVED) {
                // todo check changes for not cascaded relations?
                \Cycle\ORM\Transaction\Pool::DEBUG && print "\033[32m  Slave {$role}.{$name}\033[0m skip {$className}\n";
                continue;
            }

            $isWaitingKeys = array_intersect($relation->getInnerKeys(), $tuple->state->getWaitingFields(true)) !== [];
            $hasChangedKeys = array_intersect($relation->getInnerKeys(), $changedFields) !== [];
            if ($relationStatus === RelationInterface::STATUS_PREPARE) {
                // $relData ??= $tuple->mapper->extract($tuple->entity);
                $relData ??= $tuple->mapper->fetchRelations($tuple->entity);
                // $tuple->state->setRelation($name, $relData[$name] ?? null);
                $relation->prepare(
                    $this->pool,
                    $tuple,
                    $relData[$name] ?? null,
                    $isWaitingKeys || $hasChangedKeys
                );
                $relationStatus = $tuple->node->getRelationStatus($relation->getName());
            }

            if ($relationStatus !== RelationInterface::STATUS_PREPARE && $relationStatus !== RelationInterface::STATUS_RESOLVED && !$isWaitingKeys
                && !$hasChangedKeys
                && \count(array_intersect($relation->getInnerKeys(), array_keys($transactData))) === \count($relation->getInnerKeys())
            ) {
                \Cycle\ORM\Transaction\Pool::DEBUG && print "\033[32m  Slave {$role}.{$name}\033[0m resolve {$className}\n";
                $child ??= $tuple->state->getRelation($name);
                $relation->queue($this->pool, $tuple);
                $relationStatus = $tuple->node->getRelationStatus($relation->getName());
            } elseif ($relationStatus === RelationInterface::STATUS_RESOLVED) {
                \Cycle\ORM\Transaction\Pool::DEBUG && print "\033[32m  Slave {$role}.{$name}\033[0m resolved {$className}\n";
            } else {
                \Cycle\ORM\Transaction\Pool::DEBUG && print "\033[34m  Slave {$role}.{$name}\033[0m process {$relationStatus} {$className}\n";
            }
            $resolved = $resolved && $relationStatus === RelationInterface::STATUS_RESOLVED;
            $deferred = $deferred || $relationStatus === RelationInterface::STATUS_DEFERRED;
        }

        return ($deferred ? self::RELATIONS_DEFERRED : 0) | ($resolved ? self::RELATIONS_RESOLVED : 0);
    }

    private function resolveSelfWithEmbedded(Tuple $tuple, RelationMap $map, bool $hasDeferredRelations): void
    {
        if (!$map->hasEmbedded() && !$tuple->node->hasChanges()) {
            \Cycle\ORM\Transaction\Pool::DEBUG && print "No changes, no embedded \n";
            $tuple->status = !$hasDeferredRelations
                ? Tuple::STATUS_PROCESSED
                : max(Tuple::STATUS_DEFERRED, $tuple->status);
            return;
        }
        $command = $this->generateStoreCommand($tuple);

        if (!$map->hasEmbedded()) {
            // Not embedded but has changes
            $this->runCommand($command);

            if ($tuple->status <= Tuple::STATUS_PROPOSED && $hasDeferredRelations) {
                $tuple->status = Tuple::STATUS_DEFERRED;
            } else {
                $tuple->status = Tuple::STATUS_PROCESSED;
            }
            return;
        }

        $entityData = $tuple->mapper->extract($tuple->entity);
        // todo: use class MergeCommand here
        foreach ($map->getEmbedded() as $name => $relation) {
            $relationStatus = $tuple->node->getRelationStatus($relation->getName());
            if ($relationStatus === RelationInterface::STATUS_RESOLVED) {
                continue;
            }
            $tuple->state->setRelation($name, $entityData[$name] ?? null);
            $relation->queue(
                $this->pool,
                $tuple,
                $command instanceof Sequence ? $command->getPrimaryCommand() : $command
            );
        }
        $this->runCommand($command);

        $tuple->status = $tuple->status === Tuple::STATUS_PREPROCESSED || !$hasDeferredRelations
            ? Tuple::STATUS_PROCESSED
            : max(Tuple::STATUS_DEFERRED, $tuple->status);
    }

    private function resolveRelations(Tuple $tuple): void
    {
        $map = $this->orm->getRelationMap($tuple->node?->getRole() ?? $tuple->entity::class);

        // Dependency relations
        $result = $tuple->task === Tuple::TASK_STORE
            ? $this->resolveMasterRelations($tuple, $map)
            : $this->resolveSlaveRelations($tuple, $map);
        $isDependenciesResolved = (bool)($result & self::RELATIONS_RESOLVED);
        $deferred = (bool)($result & self::RELATIONS_DEFERRED);

        // Self
        if ($deferred && $tuple->status < Tuple::STATUS_PROPOSED) {
            $tuple->status = Tuple::STATUS_DEFERRED;
            // $this->pool->attachTuple($tuple);
        }
        if ($isDependenciesResolved) {
            if ($tuple->task === Tuple::TASK_STORE) {
                $this->resolveSelfWithEmbedded($tuple, $map, $deferred);
            } elseif ($tuple->status === Tuple::STATUS_PREPARING) {
                $tuple->status = Tuple::STATUS_WAITING;
            } else {
                $command = $this->generateDeleteCommand($tuple);
                $this->runCommand($command);
                $tuple->status = Tuple::STATUS_PROCESSED;
            }
        }

        if ($tuple->cascade) {
            // Slave relations relations
            $tuple->task === Tuple::TASK_STORE
                ? $this->resolveSlaveRelations($tuple, $map)
                : $this->resolveMasterRelations($tuple, $map);
        }

        if (!$isDependenciesResolved) {
            if ($tuple->status === Tuple::STATUS_PREPROCESSED) {
                if (\Cycle\ORM\Transaction\Pool::DEBUG) {
                    echo " \033[31m MASTER RELATIONS IS NOT RESOLVED ({$tuple->node->getRole()}): \033[0m \n";
                    foreach ($map->getMasters() as $name => $relation) {
                        $relationStatus = $tuple->node->getRelationStatus($relation->getName());
                        if ($relationStatus !== RelationInterface::STATUS_RESOLVED) {
                            echo " - \033[31m $name [$relationStatus] " . $relation::class . "\033[0m\n";
                        }
                    }
                }
                throw new TransactionException('Relation can not be resolved.');
            }
        }
    }

    public function generateStoreCommand(Tuple $tuple): ?CommandInterface
    {
        $tuple->state = $tuple->state ?? $tuple->node->getState();
        $isNew = $tuple->node->getStatus() === Node::NEW;
        $tuple->state->setStatus($isNew ? Node::SCHEDULED_INSERT : Node::SCHEDULED_UPDATE);
        $schema = $this->orm->getSchema();

        /**
         * @see \Cycle\ORM\MapperInterface::queueCreate()
         * @see \Cycle\ORM\MapperInterface::queueUpdate()
         */
        $method = $isNew ? 'queueCreate' : 'queueUpdate';

        $parents = $commands = [];
        $parent = $schema->define($tuple->node->getRole(), SchemaInterface::PARENT);
        while ($parent !== null) {
            array_unshift($parents, $parent);
            $parent = $schema->define($parent, SchemaInterface::PARENT);
        }
        foreach ($parents as $parent) {
            $parentMapper = $this->orm->getMapper($parent);
            $commands[$parent] = $parentMapper->$method($tuple->entity, $tuple->node, $tuple->state);
        }
        $commands[$tuple->node->getRole()] = $tuple->mapper->$method($tuple->entity, $tuple->node, $tuple->state);

        return \count($commands) === 1 ? current($commands) : $this->buildStoreSequence($commands, $tuple);
    }

    public function generateDeleteCommand(Tuple $tuple): CommandInterface
    {
        // currently we rely on db to delete all nested records (or soft deletes)
        return $tuple->mapper->queueDelete($tuple->entity, $tuple->node, $tuple->node->getState());
    }

    /**
     * Indexable node fields.
     *
     * todo: deduplicate with {@see \Cycle\ORM\ORM::getIndexes}
     */
    private function getIndexes(string $role): array
    {
        if (isset($this->indexes[$role])) {
            return $this->indexes[$role];
        }

        $pk = $this->orm->getSchema()->define($role, Schema::PRIMARY_KEY);
        $keys = $this->orm->getSchema()->define($role, Schema::FIND_BY_KEYS) ?? [];

        return $this->indexes[$role] = array_merge([$pk], $keys);
    }

    /**
     * @param array<string, StoreCommandInterface> $commands
     */
    private function buildStoreSequence(array $commands, Tuple $tuple): CommandInterface
    {
        $parent = null;
        $schema = $this->orm->getSchema();
        $result = [];
        foreach ($commands as $role => $command) {
            // Current parent has no parent
            if ($parent === null) {
                $result[] = $command;
                $parent = $role;
                continue;
            }

            $command = WrappedStoreCommand::wrapStoreCommand($command);

            // Transact PK from previous parent to current
            $parentKey = (array)($schema->define($role, SchemaInterface::PARENT_KEY)
                ?? $schema->define($parent, SchemaInterface::PRIMARY_KEY));
            $primaryKey = (array)$schema->define($role, SchemaInterface::PRIMARY_KEY);
            $result[] = $command->withBeforeExecution(
                static function (StoreCommandInterface $command) use ($tuple, $parentKey, $primaryKey): void {
                    foreach ($primaryKey as $i => $pk) {
                        $command->registerAppendix($pk, $tuple->state->getValue($parentKey[$i]));
                    }
                }
            );
            $parent = $role;
        }

        return (new Sequence())->addCommand(...$result);
    }
}
