<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation\Embedded;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Mockery\MockInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Embedded::class)]
final class EmbeddedTest
{
    private EntityProviderInterface&MockInterface $provider;
    private MapperInterface&MockInterface $mapper;

    public function collectPassesNullAndObjects(): void
    {
        $relation = $this->relation();
        $address = new \stdClass();

        Assert::null($relation->collect(null));
        Assert::same($relation->collect($address), $address);
    }

    public function resolveReturnsLoadedValueWithoutQuery(): void
    {
        $relation = $this->relation();
        $address = new \stdClass();
        $reference = new Reference('address', ['id' => 1]);
        $reference->setValue($address);
        $this->provider->allows('get')->andReturn(null);

        Assert::same($relation->resolve($reference, true), $address);
    }

    public function resolveRemembersMissingEntityOnlyWhenLoading(): void
    {
        $relation = $this->relation();
        $this->provider->allows('get')->andReturn(null);
        $lookup = new Reference('address', ['id' => 1]);
        $load = new Reference('address', ['id' => 1]);

        $relation->resolve($lookup, false);
        $relation->resolve($load, true);

        Assert::false($lookup->hasValue());
        Assert::true($load->hasValue());
        Assert::null($load->getValue());
    }

    public function resolveRemembersFoundEntity(): void
    {
        $relation = $this->relation();
        $address = new \stdClass();
        $this->provider->allows('get')->with('address', ['id' => 1], false)->andReturn($address);
        $reference = new Reference('address', ['id' => 1]);

        $relation->resolve($reference, false);

        Assert::true($reference->hasValue());
        Assert::same($reference->getValue(), $address);
    }

    public function queueSkipsUnchangedReferenceThatIsNotLoaded(): void
    {
        $relation = $this->relation();
        $reference = new Reference('address', ['id' => 1]);
        $address = new \stdClass();
        $this->provider->allows('get')->andReturn($address);

        $this->inIteration(static function (Pool $pool, Tuple $tuple) use ($relation, $reference): void {
            $tuple->node->setRelation('address', $reference);
            $tuple->state->setRelation('address', $reference);

            $relation->queue($pool, $tuple);
        }, $pool, $tuple);

        Assert::null($pool->offsetGet($address));
        Assert::same($tuple->state->getRelationStatus('address'), RelationInterface::STATUS_PREPARE);
    }

    public function queueStoresLoadedValueOfUnchangedReference(): void
    {
        $relation = $this->relation();
        $address = new \stdClass();
        $reference = new Reference('address', ['id' => 1]);
        $reference->setValue($address);

        $this->inIteration(static function (Pool $pool, Tuple $tuple) use ($relation, $reference): void {
            $tuple->node->setRelation('address', $reference);
            $tuple->state->setRelation('address', $reference);

            $relation->queue($pool, $tuple);
        }, $pool, $tuple);

        Assert::same($tuple->state->getRelation('address'), $address);
        Assert::same($tuple->state->getRelationStatus('address'), RelationInterface::STATUS_RESOLVED);
        Assert::same($tuple->state->getRelationStatus('user_address:address'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueStoresCopyOfAnotherEntityEmbedding(): void
    {
        $relation = $this->relation();
        $address = new \stdClass();
        $reference = new Reference('address', ['id' => 2]);
        $reference->setValue($address);

        $this->inIteration(static function (Pool $pool, Tuple $tuple) use ($relation, $reference): void {
            $tuple->state->setRelation('address', $reference);

            $relation->queue($pool, $tuple);
        }, $pool, $tuple);

        $copy = $tuple->state->getRelation('address');
        Assert::instanceOf($copy, \stdClass::class);
        Assert::notSame($copy, $address);
        Assert::false($pool->offsetGet($copy)->cascade);
    }

    public function queueWritesOnlyMappedColumnsToCommand(): void
    {
        $relation = $this->relation();
        $address = new \stdClass();
        $this->mapper->allows('extract')->andReturn(['city' => 'Paris', 'note' => 'x', 'id' => 9]);
        $command = m::mock(StoreCommandInterface::class);
        $command->expects('registerColumn')->with('city', 'Paris')->once();

        $this->inIteration(static function (Pool $pool, Tuple $tuple) use ($relation, $address, $command): void {
            $tuple->state->setRelation('address', $address);

            $relation->queue($pool, $tuple, $command);
        });
    }

    public function isAlwaysCascadeToTheEmbeddedRole(): void
    {
        $relation = $this->relation();

        Assert::same($relation->getTarget(), 'user_address');
        Assert::true($relation->isCascade());
    }

    public function castUsesTargetMapperAndKeepsNull(): void
    {
        $relation = $this->relation();
        $this->mapper->allows('cast')->with(['city' => 'Paris'])->andReturn(['city' => 'PARIS']);

        Assert::null($relation->cast(null));
        Assert::same($relation->cast(['city' => 'Paris']), ['city' => 'PARIS']);
    }

    public function referenceOfNodeWithoutKeyIsResolvedToNull(): void
    {
        $reference = $this->relation()->initReference(new Node(Node::NEW, ['id' => null], 'user'));

        Assert::instanceOf($reference, Reference::class);
        Assert::same($reference->getScope(), []);
        Assert::true($reference->hasValue());
        Assert::null($reference->getValue());
    }

    public function referenceOfStoredNodeIsScopedByKey(): void
    {
        $reference = $this->relation()->initReference(new Node(Node::MANAGED, ['id' => 3], 'user'));

        Assert::same($reference->getRole(), 'user_address');
        Assert::same($reference->getScope(), ['id' => 3]);
        Assert::false($reference->hasValue());
    }

    public function queueIgnoresDeletion(): void
    {
        $relation = $this->relation();
        $tuple = new Tuple(Tuple::TASK_DELETE, new \stdClass(), false, Tuple::STATUS_PREPARING);
        $tuple->state = new State(Node::MANAGED, ['id' => 1]);
        $tuple->state->setRelation('address', new \stdClass());

        $relation->queue(new Pool(m::mock(ORMInterface::class)), $tuple);

        Assert::same($tuple->state->getRelationStatus('address'), RelationInterface::STATUS_PREPARE);
    }

    public function queueWithoutEmbeddedValueIsResolved(): void
    {
        $relation = $this->relation();

        $this->inIteration(static function (Pool $pool, Tuple $tuple) use ($relation): void {
            $relation->queue($pool, $tuple);
        }, $pool, $tuple);

        Assert::false($tuple->state->hasRelation('address'));
        Assert::same($tuple->state->getRelationStatus('address'), RelationInterface::STATUS_RESOLVED);
    }

    /**
     * Runs the callback while the pool is iterating, the way the unit of work queues relations,
     * so that the embedded entity gets its node and state.
     *
     * @param \Closure(Pool, Tuple): void $callback
     */
    private function inIteration(\Closure $callback, ?Pool &$pool = null, ?Tuple &$tuple = null): void
    {
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getHeap')->andReturn(new Heap());
        $orm->allows('getMapper')->andReturn($this->mapper);
        $pool = new Pool($orm);
        $pool->attachStore(new \stdClass(), false, new Node(Node::MANAGED, ['id' => 1], 'user'));

        foreach ($pool->openIterator() as $tuple) {
            $callback($pool, $tuple);
            return;
        }
    }

    private function relation(): Embedded
    {
        $this->provider = m::mock(EntityProviderInterface::class);
        $this->mapper = m::mock(MapperInterface::class);
        $this->mapper->allows('getRole')->andReturn('user_address');
        $this->mapper->allows('fetchFields')->andReturn([]);
        $this->mapper->allows('extract')->andReturn(['city' => 'Paris'])->byDefault();
        $this->mapper->allows('uncast')->andReturnArg(0);
        $this->mapper->allows('mapColumns')->andReturnUsing(static fn(array &$values): array => $values);
        $mappers = m::mock(MapperProviderInterface::class);
        $mappers->allows('getMapper')->with('user_address')->andReturn($this->mapper);
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('define')->with('user_address', SchemaInterface::PRIMARY_KEY)->andReturn('id');
        $schema->allows('define')->with('user_address', SchemaInterface::COLUMNS)->andReturn(['city' => 'city', 'id' => 'id']);
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        $orm->allows('getService')->with(MapperProviderInterface::class)->andReturn($mappers);
        $orm->allows('getService')->with(EntityProviderInterface::class)->andReturn($this->provider);

        return new Embedded($orm, 'address', 'user_address');
    }
}
