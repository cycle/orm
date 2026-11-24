<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BelongsTo;
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
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(BelongsTo::class)]
final class BelongsToTest
{
    private EntityProviderInterface&MockInterface $provider;
    private Pool $pool;

    public function prepareWithoutValueAndRelationIsDeferred(): void
    {
        $tuple = $this->tuple();

        $this->relation()->prepare($this->pool, $tuple, SpecialValue::notSet());

        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_DEFERRED);
    }

    public function prepareKeepsPrefilledValueWhenNodeRelationIsUnchanged(): void
    {
        $user = new \stdClass();
        $prefill = new Reference('user', ['id' => 1]);
        $tuple = $this->tuple();
        $this->provider->allows('get')->andReturn(null);
        $tuple->node->setRelation('user', $user);
        $tuple->state->setRelation('user', $prefill);

        $this->relation()->prepare($this->pool, $tuple, $user);

        Assert::same($tuple->state->getRelation('user'), $prefill);
        Assert::null($this->pool->offsetGet($user));
    }

    public function prepareEntityWaitsForKeysAndStoresIt(): void
    {
        $user = new \stdClass();
        $tuple = $this->tuple();

        $this->relation()->prepare($this->pool, $tuple, $user);

        Assert::same($tuple->state->getRelation('user'), $user);
        Assert::same($tuple->state->getWaitingFields(true), ['user_id']);
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_PROCESS);
        Assert::true($this->pool->offsetGet($user)->cascade);
    }

    public function prepareReplacesResolvableReferenceWithEntity(): void
    {
        $user = new \stdClass();
        $tuple = $this->tuple();
        $this->provider->allows('get')->with('user', ['id' => 1], false)->andReturn($user);

        $this->relation()->prepare($this->pool, $tuple, new Reference('user', ['id' => 1]));

        Assert::same($tuple->state->getRelation('user'), $user);
    }

    public function prepareNullOnRequiredRelationWaitsForUnchangedKeys(): void
    {
        $tuple = $this->tuple();

        $this->relation()->prepare($this->pool, $tuple, null);

        Assert::same($tuple->state->getWaitingFields(true), ['user_id']);
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_PROCESS);
    }

    public function queueReplacesLoadedReferenceWithItsValue(): void
    {
        $user = new \stdClass();
        $reference = new Reference('user', ['id' => 1]);
        $reference->setValue($user);
        $tuple = $this->tuple();
        $this->attachParent($user, Tuple::STATUS_PREPARING, []);
        $tuple->state->setRelation('user', $reference);

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelation('user'), $user);
    }

    public function queueReferenceByOuterKeyRegistersInnerKey(): void
    {
        $tuple = $this->tuple();
        $tuple->state->setRelation('user', new Reference('user', ['id' => 7]));

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getData()['user_id'], 7);
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueReferenceByOtherFieldsWaitsForIt(): void
    {
        $tuple = $this->tuple();
        $tuple->state->setRelation('user', new Reference('user', ['email' => 'a@b.c']));
        $this->provider->allows('get')->andReturn(null);

        $this->relation()->queue($this->pool, $tuple);

        Assert::false(\array_key_exists('user_id', $tuple->state->getData()));
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_PREPARE);
    }

    public function queueReferenceByOtherFieldsOfWaitedEntityIsResolved(): void
    {
        $tuple = $this->tuple([], Tuple::STATUS_WAITED);
        $tuple->state->setRelation('user', new Reference('user', ['email' => 'a@b.c']));
        $this->provider->allows('get')->andReturn(null);

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_RESOLVED);
    }

    public function queuePullsKeyFromStoredParent(): void
    {
        $tuple = $this->tuple();
        $user = new \stdClass();
        $tuple->state->setRelation('user', $user);
        $this->attachParent($user, Tuple::STATUS_DEFERRED_RESOLVED, ['id' => 5]);

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getData()['user_id'], 5);
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueDoesNotPullFromParentThatIsNotStoredYet(): void
    {
        $tuple = $this->tuple();
        $user = new \stdClass();
        $tuple->state->setRelation('user', $user);
        $this->attachParent($user, Tuple::STATUS_DEFERRED, ['id' => 5]);

        $this->relation()->queue($this->pool, $tuple);

        Assert::false(\array_key_exists('user_id', $tuple->state->getData()));
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_PREPARE);
    }

    public function queuePullsEarlierWhenInversionIsResolved(): void
    {
        $tuple = $this->tuple();
        $user = new \stdClass();
        $tuple->state->setRelation('user', $user);
        $parent = $this->attachParent($user, Tuple::STATUS_DEFERRED, ['id' => 5]);
        $parent->state->setRelationStatus('posts', RelationInterface::STATUS_RESOLVED);

        $this->relation([Relation::INVERSION => 'posts'])->queue($this->pool, $tuple);

        Assert::same($tuple->state->getData()['user_id'], 5);
    }

    public function queueWithUnresolvedInversionWaitsForStoredParent(): void
    {
        $tuple = $this->tuple();
        $user = new \stdClass();
        $tuple->state->setRelation('user', $user);
        $this->attachParent($user, Tuple::STATUS_DEFERRED, ['id' => 5]);

        $this->relation([Relation::INVERSION => 'posts'])->queue($this->pool, $tuple);

        Assert::false(\array_key_exists('user_id', $tuple->state->getData()));
    }

    public function queueKeepsUnchangedKeyOfStoredParent(): void
    {
        $user = new \stdClass();
        $tuple = $this->tuple(['user_id' => 5]);
        $tuple->state->setRelation('user', $user);
        $tuple->state->waitField('user_id');
        $this->attachParent($user, Tuple::STATUS_DEFERRED_RESOLVED, ['id' => 5]);

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelation('user'), $user);
        Assert::same($tuple->state->getWaitingFields(), ['user_id']);
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueSwitchesToReferenceWhenKeyWasChangedManually(): void
    {
        $user = new \stdClass();
        $tuple = $this->tuple(['user_id' => 5]);
        $tuple->state->register('user_id', 7);
        $tuple->state->setRelation('user', $user);
        $this->attachParent($user, Tuple::STATUS_DEFERRED_RESOLVED, ['id' => 5]);
        $this->provider->allows('get')->with('user', ['id' => 7], false)->andReturn(null);

        $this->relation()->queue($this->pool, $tuple);

        $reference = $tuple->state->getRelation('user');
        Assert::instanceOf($reference, Reference::class);
        Assert::same($reference->getScope(), ['id' => 7]);
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueSwitchesToLoadedEntityWhenKeyWasChangedManually(): void
    {
        $user = new \stdClass();
        $other = new \stdClass();
        $tuple = $this->tuple(['user_id' => 5]);
        $tuple->state->register('user_id', 7);
        $tuple->state->setRelation('user', $user);
        $this->attachParent($user, Tuple::STATUS_DEFERRED_RESOLVED, ['id' => 5]);
        $this->provider->allows('get')->with('user', ['id' => 7], false)->andReturn($other);

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelation('user'), $other);
    }

    public function queueNullsNullableRelationWhenKeyWasNulledManually(): void
    {
        $user = new \stdClass();
        $tuple = $this->tuple(['user_id' => 5]);
        $tuple->state->register('user_id', null);
        $tuple->state->setRelation('user', $user);
        $this->attachParent($user, Tuple::STATUS_DEFERRED_RESOLVED, ['id' => 5]);

        $this->relation([Relation::NULLABLE => true])->queue($this->pool, $tuple);

        Assert::true($tuple->state->hasRelation('user'));
        Assert::null($tuple->state->getRelation('user'));
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueDoesNotPullKeyThatParentHasNotGot(): void
    {
        $tuple = $this->tuple();
        $user = new \stdClass();
        $tuple->state->setRelation('user', $user);
        $this->attachParent($user, Tuple::STATUS_DEFERRED_RESOLVED, []);

        $this->relation()->queue($this->pool, $tuple);

        Assert::false(\array_key_exists('user_id', $tuple->state->getData()));
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_PREPARE);
    }

    public function queueNullOnRequiredRelationWaitsWhileEntityIsPreparing(): void
    {
        $tuple = $this->tuple();
        $tuple->state->setRelation('user', null);

        $this->relation()->queue($this->pool, $tuple);

        Assert::null($tuple->state->getRelation('user'));
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_PREPARE);
    }

    public function queueNullOnRequiredRelationOfWaitedEntityUsesKeyValue(): void
    {
        $tuple = $this->tuple(['user_id' => 5], Tuple::STATUS_WAITED);
        $tuple->state->setRelation('user', null);

        $this->relation()->queue($this->pool, $tuple);

        $reference = $tuple->state->getRelation('user');
        Assert::instanceOf($reference, Reference::class);
        Assert::same($reference->getScope(), ['id' => 5]);
        Assert::same($tuple->state->getRelationStatus('user'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueNullOnRequiredRelationAllowsKeyThatIsStillAwaited(): void
    {
        $tuple = $this->tuple([], Tuple::STATUS_WAITED);
        $tuple->state->setRelation('user', null);
        $tuple->state->waitField('user_id', false);

        $this->relation()->queue($this->pool, $tuple);

        Assert::null($tuple->state->getRelation('user'));
    }

    public function queueNullOnRequiredRelationWithoutKeyThrows(): never
    {
        $tuple = $this->tuple([], Tuple::STATUS_WAITED);
        $tuple->state->setRelation('user', null);
        $tuple->state->waitField('title');

        Expect::exception(NullException::class);

        $this->relation()->queue($this->pool, $tuple);
    }

    /**
     * @param array<string, mixed> $data Persisted data of the node.
     */
    private function tuple(array $data = [], int $status = Tuple::STATUS_PREPARING): Tuple
    {
        $this->pool = new Pool(m::mock(ORMInterface::class));
        $this->provider = m::mock(EntityProviderInterface::class);
        $node = new Node(Node::MANAGED, $data, 'post');
        $tuple = new Tuple(Tuple::TASK_STORE, new \stdClass(), true, $status);
        $tuple->node = $node;
        $tuple->state = $node->createState();
        return $tuple;
    }

    /**
     * @param array<string, mixed> $data Stored data of the parent entity.
     */
    private function attachParent(object $entity, int $status, array $data): Tuple
    {
        return $this->pool->attach(
            $entity,
            Tuple::TASK_STORE,
            false,
            new Node(Node::MANAGED, $data, 'user'),
            new State(Node::MANAGED, $data),
            $status,
        );
    }

    private function relation(array $schema = []): BelongsTo
    {
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn(m::mock(SchemaInterface::class));
        $orm->allows('getService')->with(MapperProviderInterface::class)->andReturn(m::mock(MapperProviderInterface::class));
        $orm->allows('getService')->with(EntityProviderInterface::class)->andReturn($this->provider);

        return new BelongsTo($orm, 'post', 'user', 'user', $schema + [
            Relation::INNER_KEY => 'user_id',
            Relation::OUTER_KEY => 'id',
            Relation::CASCADE => true,
            Relation::NULLABLE => false,
        ]);
    }
}
