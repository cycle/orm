<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Transaction;

use Cycle\ORM\Exception\PoolException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class PoolTest
{
    private MapperInterface $mapper;

    public function testDoubleAttachSameEntityWithNullNode(): void
    {
        $orm = m::mock(ORMInterface::class);
        $pool = new Pool($orm);
        $entity = new \stdClass();

        $pool->attach($entity, Tuple::TASK_DELETE, false);
        $pool->attach($entity, Tuple::TASK_DELETE, false);

        // There is no exception here
        Assert::true(true);
    }

    public function passiveEntityWalksThroughEveryStage(): void
    {
        $pool = $this->pool();
        $tuple = $pool->attachStore($this->entity('a'), true);

        $trace = $this->trace($pool);

        Assert::same($trace, ['a:0:0:1', 'a:2:0:1', 'a:4:0:1', 'a:6:0:1', 'a:7:0:1']);
        Assert::same($tuple->status, Tuple::STATUS_PROCESSED);
        Assert::same(\count($pool), 0);
    }

    public function entitiesTakeTurnsAtEveryStage(): void
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);
        $pool->attachStore($this->entity('b'), true);

        $trace = $this->trace($pool);

        Assert::same($trace, [
            'a:0:0:2', 'b:0:0:2',
            'a:2:0:2', 'b:2:0:2',
            'a:4:0:2', 'b:4:0:2',
            'a:6:0:2', 'b:6:0:2',
            'a:7:0:2', 'b:7:0:1',
        ]);
    }

    public function firstStageYieldsOnlyPreparingEntities(): void
    {
        $pool = $this->pool();
        $waiting = $pool->attach($this->entity('a'), Tuple::TASK_STORE, true, status: Tuple::STATUS_WAITING);
        $pool->attachStore($this->entity('b'), true);

        $trace = $this->trace($pool);

        Assert::same($waiting->status, Tuple::STATUS_PROCESSED);
        Assert::same(\array_slice($trace, 0, 3), ['b:0:0:2', 'a:2:0:2', 'b:2:0:2']);
    }

    public function deletionWaitsForStoresAndBecomesForced(): void
    {
        $pool = $this->pool();
        $state = new State(Node::MANAGED, []);
        $delete = $pool->attachDelete($this->entity('d'), true, new Node(Node::MANAGED, [], 'user'), $state);
        $pool->attachStore($this->entity('a'), true);

        $trace = $this->trace($pool);

        Assert::same($state->getStatus(), Node::SCHEDULED_DELETE);
        Assert::same($delete->task, Tuple::TASK_FORCE_DELETE);
        Assert::same(\array_slice($trace, 0, 5), ['d:0:1:2', 'a:0:0:2', 'a:2:0:2', 'd:2:2:2', 'a:4:0:2']);
    }

    public function highPriorityEntityAttachedDuringIterationGoesNext(): void
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);
        $pool->attachStore($this->entity('b'), true);
        $c = $this->entity('c');

        $trace = $this->trace($pool, static function (object $entity, Tuple $tuple) use ($pool, $c): void {
            if ($entity->n === 'a' && $tuple->status === Tuple::STATUS_PREPARING) {
                $pool->attachStore($c, true, highPriority: true);
            }
        });

        Assert::same(\array_slice($trace, 0, 4), ['a:0:0:2', 'c:0:0:3', 'b:0:0:3', 'a:2:0:3']);
        Assert::count(\array_filter($trace, static fn(string $s): bool => $s[0] === 'c'), 5);
    }

    public function entityAttachedAfterFirstStageGetsPriority(): void
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);
        $pool->attachStore($this->entity('b'), true);
        $c = $this->entity('c');

        $trace = $this->trace($pool, static function (object $entity, Tuple $tuple) use ($pool, $c): void {
            if ($entity->n === 'a' && $tuple->status === Tuple::STATUS_WAITED) {
                $pool->attachStore($c, true);
            }
        });

        Assert::same(\array_slice($trace, 2, 3), ['a:2:0:2', 'c:0:0:3', 'b:2:0:3']);
    }

    public function entityAttachedAtLastStageInterruptsIt(): void
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);
        $pool->attachStore($this->entity('b'), true);
        $c = $this->entity('c');

        $trace = $this->trace($pool, static function (object $entity, Tuple $tuple) use ($pool, $c): void {
            if ($entity->n === 'a' && $tuple->status === Tuple::STATUS_PROPOSED) {
                $pool->attachStore($c, true);
            }
        });

        Assert::same(\array_slice($trace, 4, 4), ['a:4:0:2', 'c:0:0:3', 'a:6:0:3', 'b:4:0:3']);
    }

    public function processedEntityIsKnownButNotQueued(): void
    {
        $pool = $this->pool();
        $a = $this->entity('a');

        $tuple = $pool->attach($a, Tuple::TASK_STORE, false, status: Tuple::STATUS_PROCESSED);

        Assert::same($pool->offsetGet($a), $tuple);
        Assert::same(\count($pool), 0);
        Assert::same($this->trace($pool), []);
    }

    public function presetStateOfNewEntityIsFilledOnSnap(): void
    {
        $mapper = m::mock(MapperInterface::class);
        $mapper->allows('getRole')->andReturn('user');
        $mapper->allows('fetchFields')->andReturn(['name' => 'a']);
        $pool = $this->pool($mapper);
        $state = new State(Node::NEW, []);
        $tuple = $pool->attachStore($this->entity('a'), true, state: $state);

        $pool->openIterator()->current();

        Assert::same($tuple->state, $state);
        Assert::same($state->getData(), ['name' => 'a']);
        Assert::same($tuple->node->getState(), $state);
    }

    public function storedEntityCanBeForcedToDeletion(): void
    {
        $pool = $this->pool();
        $a = $this->entity('a');
        $tuple = $pool->attachStore($a, false);

        $pool->attach($a, Tuple::TASK_FORCE_DELETE, false);

        Assert::same($tuple->task, Tuple::TASK_FORCE_DELETE);
    }

    public function unprocessedEntityIsReturnedAfterOthersProgress(): void
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);
        $pool->attachStore($this->entity('b'), true);
        $unresolved = [];

        $trace = $this->trace($pool, static function (object $entity, Tuple $tuple) use ($pool, &$unresolved): void {
            if ($entity->n === 'a' && $tuple->status === Tuple::STATUS_PREPARING) {
                $tuple->status = Tuple::STATUS_UNPROCESSED;
            } elseif ($entity->n === 'b' && $tuple->status === Tuple::STATUS_PREPARING) {
                $unresolved = \array_map(static fn(Tuple $t): string => $t->entity->n, [...$pool->getUnresolved()]);
            }
        });

        Assert::same($unresolved, ['a']);
        Assert::same($trace, ['a:0:0:2', 'b:0:0:1', 'b:2:0:1', 'b:4:0:1', 'b:6:0:2', 'a:7:0:2', 'b:7:0:1']);
    }

    public function preprocessedEntityCountsAsProgress(): void
    {
        $pool = $this->pool();
        $pool->attach($this->entity('a'), Tuple::TASK_STORE, true, status: Tuple::STATUS_PREPROCESSED);
        $pool->attachStore($this->entity('b'), true);

        $trace = $this->trace($pool);

        Assert::same($trace, ['b:0:0:2', 'b:2:0:2', 'a:7:0:2', 'b:4:0:1', 'b:6:0:1', 'b:7:0:1']);
    }

    public function externalProgressIsCounted(): void
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);
        $pool->attachStore($this->entity('b'), true);
        $deferred = false;

        $trace = $this->trace($pool, static function (object $entity, Tuple $tuple) use ($pool, &$deferred): void {
            if ($entity->n === 'a' && $tuple->status === Tuple::STATUS_PROPOSED && !$deferred) {
                $deferred = true;
                $tuple->status = Tuple::STATUS_DEFERRED;
                $pool->someHappens();
            }
        });

        Assert::count($trace, 11);
    }

    public function poolWithoutProgressThrows(): never
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);

        Expect::exception(PoolException::class)->withMessage('Pool has gone into an infinite loop.');

        $this->trace($pool, static function (object $entity, Tuple $tuple): void {
            if ($tuple->status === Tuple::STATUS_PROPOSED) {
                $tuple->status = Tuple::STATUS_DEFERRED;
            }
        });
    }

    public function iteratorCanBeOpenedAgainAfterItIsDrained(): void
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);
        $this->trace($pool);
        $pool->attachStore($this->entity('b'), true);

        $trace = $this->trace($pool);

        Assert::same($trace, ['b:0:0:1', 'b:2:0:1', 'b:4:0:1', 'b:6:0:1', 'b:7:0:1']);
    }

    public function openingIteratorTwiceThrows(): never
    {
        $pool = $this->pool();
        $pool->attachStore($this->entity('a'), true);
        $pool->openIterator()->current();

        Expect::exception(\RuntimeException::class)->withMessage('Iterator is already open.');

        $pool->openIterator()->current();
    }

    public function unresolvedRequiresOpenIterator(): never
    {
        $pool = $this->pool();

        Expect::exception(PoolException::class);

        $pool->getUnresolved();
    }

    public function reattachReturnsTheSameTuple(): void
    {
        $pool = $this->pool();
        $a = $this->entity('a');
        $tuple = $pool->attachStore($a, false);

        Assert::same($pool->attach($a, Tuple::TASK_STORE, false, status: Tuple::STATUS_PROCESSED), $tuple);
        Assert::same($pool->attachStore($a, true, persist: true), $tuple);
        Assert::true($tuple->cascade);
        Assert::same($pool->offsetGet($a), $tuple);
    }

    public function reattachWithStatusProcessedDropsTheEntity(): void
    {
        $pool = $this->pool();
        $a = $this->entity('a');
        $pool->attachStore($a, false);

        $pool->attach($a, Tuple::TASK_STORE, false, status: Tuple::STATUS_PROCESSED);

        Assert::same(\count($pool), 0);
        Assert::same($this->trace($pool), []);
    }

    public function reattachUpdatesStatusOfPreparingTuple(): void
    {
        $pool = $this->pool();
        $a = $this->entity('a');
        $tuple = $pool->attachStore($a, false);

        $pool->attach($a, Tuple::TASK_STORE, false, status: Tuple::STATUS_WAITING);

        Assert::same($tuple->status, Tuple::STATUS_WAITING);
    }

    public function reattachKeepsNodeAndState(): void
    {
        $pool = $this->pool();
        $a = $this->entity('a');
        $node = new Node(Node::MANAGED, [], 'user');
        $state = new State(Node::MANAGED, []);
        $tuple = $pool->attachStore($a, false, $node, $state);

        $pool->attachStore($a, false, new Node(Node::MANAGED, [], 'user'), new State(Node::MANAGED, []));

        Assert::same($tuple->node, $node);
        Assert::same($tuple->state, $state);
    }

    public function mapperIsResolvedByNodeRole(): void
    {
        $pool = $this->pool(m::mock(MapperInterface::class));
        $tuple = $pool->attachStore($this->entity('a'), true, new Node(Node::MANAGED, [], 'user'));

        $pool->openIterator()->current();

        Assert::same($tuple->mapper, $this->mapper);
    }

    public function presetMapperIsKept(): void
    {
        $pool = $this->pool();
        $tuple = $pool->attachStore($this->entity('a'), true);
        $mapper = m::mock(MapperInterface::class);
        $mapper->allows('getRole')->andReturn('user');
        $mapper->allows('fetchFields')->andReturn([]);
        $tuple->mapper = $mapper;

        $pool->openIterator()->current();

        Assert::same($tuple->mapper, $mapper);
    }

    /**
     * @param null|\Closure(object, Tuple): void $onYield
     *
     * @return list<non-empty-string> "<entity>:<status>:<task>:<pool size>" for every yielded tuple
     */
    private function trace(Pool $pool, ?\Closure $onYield = null): array
    {
        $trace = [];
        foreach ($pool->openIterator() as $entity => $tuple) {
            $trace[] = \sprintf('%s:%d:%d:%d', $entity->n, $tuple->status, $tuple->task, \count($pool));
            $onYield === null or $onYield($entity, $tuple);
        }
        return $trace;
    }

    private function entity(string $name): object
    {
        $entity = new \stdClass();
        $entity->n = $name;
        return $entity;
    }

    /**
     * @param MapperInterface|null $byEntity Mapper resolved by an entity object instead of a role name.
     */
    private function pool(?MapperInterface $byEntity = null): Pool
    {
        $this->mapper = m::mock(MapperInterface::class);
        $this->mapper->allows('getRole')->andReturn('user');
        $this->mapper->allows('fetchFields')->andReturn([]);
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getHeap')->andReturn(new Heap());
        $orm->allows('getMapper')->with('user')->andReturn($this->mapper);
        $orm->allows('getMapper')->with(m::type('object'))->andReturn($byEntity ?? $this->mapper);
        return new Pool($orm);
    }
}
