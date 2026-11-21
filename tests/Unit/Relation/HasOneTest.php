<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasOne;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Mockery\MockInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class HasOneTest
{
    private SchemaInterface&MockInterface $schema;
    private Pool $pool;

    public function targetIsTheRelatedRole(): void
    {
        $this->tuple();

        Assert::same($this->relation()->getTarget(), 'profile');
    }

    public function prepareAcceptsEntityOfTargetAlias(): void
    {
        $tuple = $this->tuple();
        $profile = new \stdClass();
        $this->pool->attach($profile, Tuple::TASK_STORE, false, new Node(Node::NEW, [], 'user_profile'), new State(Node::NEW, []));
        $this->schema->expects('resolveAlias')->once()->with('user_profile')->andReturn('profile');
        $relation = $this->relation();

        $relation->prepare($this->pool, $tuple, $profile);
        $relation->prepare($this->pool, $tuple, $profile);

        Assert::same($tuple->state->getRelationStatus('profile'), RelationInterface::STATUS_PROCESS);
        Assert::true($this->pool->offsetGet($profile)->cascade);
    }

    public function queueIgnoresDeletion(): void
    {
        $tuple = $this->tuple(Tuple::TASK_DELETE);
        $tuple->state->setRelation('profile', new \stdClass());

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelationStatus('profile'), RelationInterface::STATUS_PREPARE);
    }

    public function queueOfReferenceThatIsNotLoadedChangesNothing(): void
    {
        $tuple = $this->tuple();
        $reference = new Reference('profile', ['user_id' => 1]);
        $tuple->state->setRelation('profile', $reference);

        $this->relation()->queue($this->pool, $tuple);

        Assert::same($tuple->state->getRelation('profile'), $reference);
        Assert::same($tuple->state->getRelationStatus('profile'), RelationInterface::STATUS_RESOLVED);
    }

    private function tuple(int $task = Tuple::TASK_STORE): Tuple
    {
        $this->pool = new Pool(m::mock(ORMInterface::class));
        $this->schema = m::mock(SchemaInterface::class);
        $node = new Node(Node::MANAGED, ['id' => 1], 'user');
        $tuple = new Tuple($task, new \stdClass(), true, Tuple::STATUS_PREPARING);
        $tuple->node = $node;
        $tuple->state = $node->createState();
        return $tuple;
    }

    private function relation(): HasOne
    {
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($this->schema);
        $orm->allows('getService')->with(MapperProviderInterface::class)->andReturn(m::mock(MapperProviderInterface::class));
        $orm->allows('getService')->with(EntityProviderInterface::class)->andReturn(m::mock(EntityProviderInterface::class));

        return new HasOne($orm, 'user', 'profile', 'profile', [
            Relation::INNER_KEY => 'id',
            Relation::OUTER_KEY => 'user_id',
            Relation::CASCADE => true,
            Relation::NULLABLE => false,
        ]);
    }
}
