<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\RefersTo;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Relation\SpecialValue;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Mockery\MockInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataSet;
use Testo\Test;

#[Test]
#[Covers(RefersTo::class)]
final class RefersToTest
{
    private EntityProviderInterface&MockInterface $provider;
    private Pool $pool;

    public function prepareWithoutValueAndRelationIsDeferred(): void
    {
        $tuple = $this->tuple();

        $this->relation()->prepare($this->pool, $tuple, SpecialValue::notSet());

        Assert::same($tuple->state->getRelationStatus('parent'), RelationInterface::STATUS_DEFERRED);
    }

    public function prepareKeepsReferenceThatIsNotLoaded(): void
    {
        $tuple = $this->tuple();
        $reference = new Reference('comment', ['id' => 1]);
        $this->provider->allows('get')->with('comment', ['id' => 1], false)->andReturn(null);
        $this->provider->allows('get')->with('comment', ['id' => 1], true)->andReturn(new \stdClass());

        $this->relation()->prepare($this->pool, $tuple, $reference);

        Assert::same($tuple->state->getRelation('parent'), $reference);
        Assert::same($tuple->state->getRelationStatus('parent'), RelationInterface::STATUS_DEFERRED);
    }

    public function prepareReplacesLoadedReferenceWithEntity(): void
    {
        $tuple = $this->tuple();
        $parent = new \stdClass();
        $this->provider->allows('get')->with('comment', ['id' => 1], false)->andReturn($parent);

        $this->relation()->prepare($this->pool, $tuple, new Reference('comment', ['id' => 1]));

        Assert::same($tuple->state->getRelation('parent'), $parent);
        Assert::same($tuple->state->getRelationStatus('parent'), RelationInterface::STATUS_PROCESS);
    }

    public function prepareEntityWaitsForOptionalKey(): void
    {
        $tuple = $this->tuple();

        $this->relation()->prepare($this->pool, $tuple, new \stdClass());

        Assert::same($tuple->state->getWaitingFields(), ['parent_id']);
        Assert::same($tuple->state->getWaitingFields(true), []);
    }

    public function prepareStoresNewEntityWithoutCascade(): void
    {
        $tuple = $this->tuple();
        $parent = new \stdClass();

        $this->relation()->prepare($this->pool, $tuple, $parent);

        Assert::false($this->pool->offsetGet($parent)->cascade);
    }

    public function prepareDoesNotStoreEntityOfNonCascadeRelation(): void
    {
        $tuple = $this->tuple();
        $parent = new \stdClass();

        $this->relation([Relation::CASCADE => false])->prepare($this->pool, $tuple, $parent);

        Assert::null($this->pool->offsetGet($parent));
    }

    public function prepareNullResetsKeysAndNodeRelation(): void
    {
        $tuple = $this->tuple(['parent_id' => 3]);
        $tuple->node->setRelation('parent', new \stdClass());

        $this->relation()->prepare($this->pool, $tuple, null);

        Assert::null($tuple->node->getRelation('parent'));
        Assert::null($tuple->state->getData()['parent_id']);
        Assert::same($tuple->state->getRelationStatus('parent'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueReferenceByOuterKeyRegistersInnerKey(): void
    {
        $tuple = $this->tuple();
        $reference = new Reference('comment', ['id' => 1]);
        $tuple->state->setRelation('parent', $reference);
        $this->provider->allows('get')->with('comment', ['id' => 1], false)->andReturn(null);
        $this->provider->allows('get')->with('comment', ['id' => 1], true)->andReturn(new \stdClass());

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelation('parent'), $reference);
        Assert::same($tuple->node->getRelation('parent'), $reference);
        Assert::same($tuple->state->getData()['parent_id'], 1);
        Assert::same($tuple->state->getRelationStatus('parent'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueReferenceByOtherFieldsIsDeferred(): void
    {
        $tuple = $this->tuple();
        $tuple->state->setRelation('parent', new Reference('comment', ['slug' => 'x']));
        $this->provider->allows('get')->andReturn(null);

        $this->relation([Relation::CASCADE => false])->queue($this->pool, $tuple);

        Assert::false(\array_key_exists('parent_id', $tuple->state->getData()));
        Assert::same($tuple->state->getRelationStatus('parent'), RelationInterface::STATUS_DEFERRED);
    }

    public function queueReplacesLoadedReferenceWithItsValue(): void
    {
        $tuple = $this->tuple();
        $parent = new \stdClass();
        $reference = new Reference('comment', ['id' => 1]);
        $reference->setValue($parent);
        $tuple->state->setRelation('parent', $reference);

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelation('parent'), $parent);
    }

    public function queueStoresNewEntityOfCascadeRelation(): void
    {
        $tuple = $this->tuple();
        $parent = new \stdClass();
        $tuple->state->setRelation('parent', $parent);

        $this->relation()->queue($this->pool, $tuple);

        Assert::false($this->pool->offsetGet($parent)->cascade);
        Assert::same($tuple->state->getRelationStatus('parent'), RelationInterface::STATUS_PREPARE);
    }

    #[DataSet([RelationInterface::STATUS_DEFERRED, Tuple::STATUS_PROPOSED_RESOLVED, RelationInterface::STATUS_RESOLVED])]
    #[DataSet([RelationInterface::STATUS_DEFERRED, Tuple::STATUS_PROPOSED, RelationInterface::STATUS_DEFERRED])]
    #[DataSet([RelationInterface::STATUS_PROCESS, Tuple::STATUS_PROPOSED_RESOLVED, RelationInterface::STATUS_DEFERRED])]
    public function queueEntityUnknownToNonCascadeRelation(int $relationStatus, int $tupleStatus, int $expected): void
    {
        $tuple = $this->tuple([], $tupleStatus);
        $tuple->state->setRelation('parent', new \stdClass());
        $tuple->state->setRelationStatus('parent', $relationStatus);

        $this->relation([Relation::CASCADE => false])->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelationStatus('parent'), $expected);
    }

    public function queuePullsKeyFromProcessedEntity(): void
    {
        $tuple = $this->tuple();
        $parent = new \stdClass();
        $tuple->state->setRelation('parent', $parent);
        $this->attachParent($parent, Tuple::STATUS_PROCESSED, ['id' => 5]);

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getData()['parent_id'], 5);
        Assert::same($tuple->node->getRelation('parent'), $parent);
        Assert::same($tuple->state->getRelationStatus('parent'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueDoesNotPullFromEntityThatIsNotProcessedYet(): void
    {
        $tuple = $this->tuple();
        $parent = new \stdClass();
        $tuple->state->setRelation('parent', $parent);
        $this->attachParent($parent, Tuple::STATUS_PREPARING, ['id' => 5]);

        $this->relation()->queue($this->pool, $tuple);

        Assert::false(\array_key_exists('parent_id', $tuple->state->getData()));
    }

    /**
     * @param array<string, mixed> $data Persisted data of the node.
     */
    private function tuple(array $data = [], int $status = Tuple::STATUS_PREPARING): Tuple
    {
        $this->pool = new Pool(m::mock(ORMInterface::class));
        $this->provider = m::mock(EntityProviderInterface::class);
        $node = new Node(Node::MANAGED, $data, 'comment');
        $tuple = new Tuple(Tuple::TASK_STORE, new \stdClass(), true, $status);
        $tuple->node = $node;
        $tuple->state = $node->createState();
        return $tuple;
    }

    /**
     * @param array<string, mixed> $data Stored data of the referenced entity.
     */
    private function attachParent(object $entity, int $status, array $data): Tuple
    {
        return $this->pool->attach(
            $entity,
            Tuple::TASK_STORE,
            false,
            new Node(Node::MANAGED, $data, 'comment'),
            new State(Node::MANAGED, $data),
            $status,
        );
    }

    private function relation(array $schema = []): RefersTo
    {
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn(m::mock(SchemaInterface::class));
        $orm->allows('getService')->with(MapperProviderInterface::class)->andReturn(m::mock(MapperProviderInterface::class));
        $orm->allows('getService')->with(EntityProviderInterface::class)->andReturn($this->provider);

        return new RefersTo($orm, 'comment', 'parent', 'comment', $schema + [
            Relation::INNER_KEY => 'parent_id',
            Relation::OUTER_KEY => 'id',
            Relation::CASCADE => true,
            Relation::NULLABLE => true,
        ]);
    }
}
