<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Special\Sequence;
use Cycle\ORM\Exception\PoolException;
use Cycle\ORM\Exception\SuccessTransactionRetryException;
use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Service\IndexProviderInterface;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Relation\ShadowBelongsTo;
use Cycle\ORM\RelationMap;

final class UnitOfWork implements StateInterface
{
    private const RELATIONS_NOT_RESOLVED = 0;
    private const RELATIONS_RESOLVED = 1;
    private const RELATIONS_DEFERRED = 2;

    private const STAGE_PREPARING = 0;
    private const STAGE_PROCESS = 1;
    private const STAGE_FINISHED = 2;

    private int $stage = self::STAGE_PREPARING;
    private Pool $pool;
    private CommandGeneratorInterface $commandGenerator;
    private ?\Throwable $error = null;

    public function __construct(
        private ORMInterface $orm,
        private ?RunnerInterface $runner = null
    ) {
        $this->pool = new Pool($orm);
        $this->commandGenerator = $orm->getCommandGenerator();
    }

    public function isSuccess(): bool
    {
        return $this->stage === self::STAGE_FINISHED;
    }

    public function getLastError(): ?\Throwable
    {
        return $this->error;
    }

    public function retry(): static
    {
        return $this->run();
    }

    public function persistState(object $entity, bool $cascade = true): self
    {
        $this->checkActionPossibility('persist entity');
        $this->pool->attachStore($entity, $cascade, persist: true);

        return $this;
    }

    public function persistDeferred(object $entity, bool $cascade = true): self
    {
        $this->checkActionPossibility('schedule entity storing');
        $this->pool->attachStore($entity, $cascade);

        return $this;
    }

    public function delete(object $entity, bool $cascade = true): self
    {
        $this->checkActionPossibility('schedule entity deletion');
        $this->pool->attach($entity, Tuple::TASK_FORCE_DELETE, $cascade);

        return $this;
    }

    public function run(): StateInterface
    {
        $this->stage = match ($this->stage) {
            self::STAGE_FINISHED => throw new SuccessTransactionRetryException(
                'A successful transaction cannot be re-run.'
            ),
            self::STAGE_PROCESS => throw new TransactionException('Can\'t run started transaction.'),
            default => self::STAGE_PROCESS,
        };

        $this->runner ??= Runner::innerTransaction();

        try {
            try {
                $this->walkPool();
            } catch (PoolException $e) {
                // Generate detailed exception about unresolved relations
                throw TransactionException::unresolvedRelations(
                    $this->pool->getUnresolved(),
                    $this->orm->getService(RelationProviderInterface::class),
                    $e,
                );
            }
        } catch (\Throwable $e) {
            $this->runner->rollback();
            $this->pool->closeIterator();

            // no calculations must be kept in node states, resetting
            // this will keep entity data as it was before transaction run
            $this->resetHeap();

            $this->error = $e;
            $this->stage = self::STAGE_PREPARING;

            return $this;
        }

        // we are ready to commit all changes to our representation layer
        $this->syncHeap();

        $this->runner->complete();
        $this->error = null;
        $this->stage = self::STAGE_FINISHED;
        // Clear state
        unset($this->orm, $this->runner, $this->pool, $this->commandGenerator);

        return $this;
    }

    public function setRunner(RunnerInterface $runner): void
    {
        $this->runner = $runner;
    }

    /**
     * @throws TransactionException
     */
    private function checkActionPossibility(string $action): void
    {
        $this->stage === self::STAGE_PROCESS && throw new TransactionException("Can't $action. Transaction began.");
        $this->stage === self::STAGE_FINISHED && throw new TransactionException("Can't $action. Transaction finished.");
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
        $relationProvider = $this->orm->getService(RelationProviderInterface::class);
        $indexProvider = $this->orm->getService(IndexProviderInterface::class);
        foreach ($this->pool->getAllTuples() as $e => $tuple) {
            $node = $tuple->node;

            // marked as being deleted and has no external claims (GC like approach)
            if (\in_array($node->getStatus(), [Node::DELETED, Node::SCHEDULED_DELETE], true)) {
                $heap->detach($e);
                continue;
            }
            $role = $node->getRole();

            // reindex the entity while it has old data
            $node->setState($tuple->state);
            $heap->attach($e, $node, $indexProvider->getIndexes($role));

            if ($tuple->persist !== null) {
                $syncData = \array_udiff_assoc(
                    $tuple->state->getData(),
                    $tuple->persist->getData(),
                    [Node::class, 'compare']
                );
            } else {
                $syncData = $node->syncState($relationProvider->getRelationMap($role), $tuple->state);
            }

            $tuple->mapper->hydrate($e, $syncData);
        }
    }

    /**
     * Reset heap to it's initial state and remove all the changes.
     */
    private function resetHeap(): void
    {
        foreach ($this->pool->getAllTuples() as $tuple) {
            $tuple->node->resetState();
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
        foreach ($this->pool->openIterator() as $tuple) {
            if ($tuple->task === Tuple::TASK_FORCE_DELETE && !$tuple->cascade) {
                // currently we rely on db to delete all nested records (or soft deletes)
                $command = $this->commandGenerator->generateDeleteCommand($this->orm, $tuple);
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
        foreach ($map->getMasters() as $name => $relation) {
            $relationStatus = $tuple->state->getRelationStatus($relation->getName());
            if ($relationStatus === RelationInterface::STATUS_RESOLVED) {
                continue;
            }

            if ($relation instanceof ShadowBelongsTo) {
                // Check relation is connected
                // Connected -> $parentNode->getRelationStatus()
                // Disconnected -> WAIT if Tuple::STATUS_PREPARING
                $relation->queue($this->pool, $tuple);
                $relationStatus = $tuple->state->getRelationStatus($relation->getName());
            } else {
                if ($tuple->status === Tuple::STATUS_PREPARING) {
                    if ($relationStatus === RelationInterface::STATUS_PREPARE) {
                        $entityData ??= $tuple->mapper->fetchRelations($tuple->entity);
                        $relation->prepare($this->pool, $tuple, $entityData[$name] ?? null);
                        $relationStatus = $tuple->state->getRelationStatus($relation->getName());
                    }
                } else {
                    $relation->queue($this->pool, $tuple);
                    $relationStatus = $tuple->state->getRelationStatus($relation->getName());
                }
            }
            $resolved = $resolved && $relationStatus >= RelationInterface::STATUS_DEFERRED;
            $deferred = $deferred || $relationStatus === RelationInterface::STATUS_DEFERRED;
        }

        // $tuple->waitKeys = array_unique(array_merge(...$waitKeys));
        return ($deferred ? self::RELATIONS_DEFERRED : 0) | ($resolved ? self::RELATIONS_RESOLVED : 0);
    }

    private function resolveSlaveRelations(Tuple $tuple, RelationMap $map): int
    {
        if (!$map->hasSlaves()) {
            return self::RELATIONS_RESOLVED;
        }
        $changedFields = \array_keys($tuple->state->getChanges());

        // Attach children to pool
        $transactData = $tuple->state->getTransactionData();
        $deferred = false;
        $resolved = true;
        if ($tuple->status === Tuple::STATUS_PREPARING) {
            $relData = $tuple->mapper->fetchRelations($tuple->entity);
        }
        foreach ($map->getSlaves() as $name => $relation) {
            $relationStatus = $tuple->state->getRelationStatus($relation->getName());
            if (!$relation->isCascade() || $relationStatus === RelationInterface::STATUS_RESOLVED) {
                continue;
            }

            $innerKeys = $relation->getInnerKeys();
            $isWaitingKeys = \array_intersect($innerKeys, $tuple->state->getWaitingFields(true)) !== [];
            $hasChangedKeys = \array_intersect($innerKeys, $changedFields) !== [];
            if ($relationStatus === RelationInterface::STATUS_PREPARE) {
                $relData ??= $tuple->mapper->fetchRelations($tuple->entity);
                $relation->prepare(
                    $this->pool,
                    $tuple,
                    $relData[$name] ?? null,
                    $isWaitingKeys || $hasChangedKeys,
                );
                $relationStatus = $tuple->state->getRelationStatus($relation->getName());
            }

            if ($relationStatus !== RelationInterface::STATUS_PREPARE
                && $relationStatus !== RelationInterface::STATUS_RESOLVED
                && !$isWaitingKeys
                && !$hasChangedKeys
                && \count(\array_intersect($innerKeys, \array_keys($transactData))) === \count($innerKeys)
            ) {
                // $child ??= $tuple->state->getRelation($name);
                $relation->queue($this->pool, $tuple);
                $relationStatus = $tuple->state->getRelationStatus($relation->getName());
            }
            $resolved = $resolved && $relationStatus === RelationInterface::STATUS_RESOLVED;
            $deferred = $deferred || $relationStatus === RelationInterface::STATUS_DEFERRED;
        }

        return ($deferred ? self::RELATIONS_DEFERRED : 0) | ($resolved ? self::RELATIONS_RESOLVED : 0);
    }

    private function resolveSelfWithEmbedded(Tuple $tuple, RelationMap $map, bool $hasDeferredRelations): void
    {
        if (!$map->hasEmbedded() && !$tuple->state->hasChanges()) {
            $tuple->status = !$hasDeferredRelations
                ? Tuple::STATUS_PROCESSED
                : \max(Tuple::STATUS_DEFERRED, $tuple->status);

            return;
        }
        $command = $this->commandGenerator->generateStoreCommand($this->orm, $tuple);

        if (!$map->hasEmbedded()) {
            // Not embedded but has changes
            $this->runCommand($command);

            $tuple->status = $tuple->status <= Tuple::STATUS_PROPOSED && $hasDeferredRelations
                ? Tuple::STATUS_DEFERRED
                : Tuple::STATUS_PROCESSED;

            return;
        }

        $entityData = $tuple->mapper->extract($tuple->entity);
        foreach ($map->getEmbedded() as $name => $relation) {
            $relationStatus = $tuple->state->getRelationStatus($relation->getName());
            if ($relationStatus === RelationInterface::STATUS_RESOLVED) {
                continue;
            }
            $tuple->state->setRelation($name, $entityData[$name] ?? null);
            // We can use class MergeCommand here
            $relation->queue(
                $this->pool,
                $tuple,
                $command instanceof Sequence ? $command->getPrimaryCommand() : $command
            );
        }
        $this->runCommand($command);

        $tuple->status = $tuple->status === Tuple::STATUS_PREPROCESSED || !$hasDeferredRelations
            ? Tuple::STATUS_PROCESSED
            : \max(Tuple::STATUS_DEFERRED, $tuple->status);
    }

    private function resolveRelations(Tuple $tuple): void
    {
        $map = $this->orm->getRelationMap($tuple->node->getRole());

        $result = $tuple->task === Tuple::TASK_STORE
            ? $this->resolveMasterRelations($tuple, $map)
            : $this->resolveSlaveRelations($tuple, $map);
        $isDependenciesResolved = (bool)($result & self::RELATIONS_RESOLVED);
        $deferred = (bool)($result & self::RELATIONS_DEFERRED);

        // Self
        if ($deferred && $tuple->status < Tuple::STATUS_PROPOSED) {
            $tuple->status = Tuple::STATUS_DEFERRED;
        }

        if ($isDependenciesResolved) {
            if ($tuple->task === Tuple::TASK_STORE) {
                $this->resolveSelfWithEmbedded($tuple, $map, $deferred);
            } elseif ($tuple->status === Tuple::STATUS_PREPARING) {
                $tuple->status = Tuple::STATUS_WAITING;
            } else {
                $command = $this->commandGenerator->generateDeleteCommand($this->orm, $tuple);
                $this->runCommand($command);
                $tuple->status = Tuple::STATUS_PROCESSED;
            }
        }

        if ($tuple->cascade) {
            $tuple->task === Tuple::TASK_STORE
                ? $this->resolveSlaveRelations($tuple, $map)
                : $this->resolveMasterRelations($tuple, $map);
        }

        if (!$isDependenciesResolved && $tuple->status === Tuple::STATUS_PREPROCESSED) {
            $tuple->status = Tuple::STATUS_UNPROCESSED;
        }
    }
}
