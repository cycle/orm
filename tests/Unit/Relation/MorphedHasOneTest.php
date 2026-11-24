<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\Morphed\MorphedHasOne;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class MorphedHasOneTest
{
    public function queueOfReferenceThatIsNotLoadedLeavesMorphKey(): void
    {
        $pool = new Pool(m::mock(ORMInterface::class));
        $tuple = $this->tuple(Tuple::TASK_STORE);
        $reference = new Reference('image', ['parent_id' => 1, 'parent_type' => 'user']);
        $tuple->state->setRelation('image', $reference);

        $this->relation()->queue($pool, $tuple);

        Assert::same($tuple->state->getRelation('image'), $reference);
        Assert::same($tuple->state->getRelationStatus('image'), RelationInterface::STATUS_RESOLVED);
    }

    public function queueOfDeletionSkipsEntityOutOfTransaction(): void
    {
        $pool = new Pool(m::mock(ORMInterface::class));
        $tuple = $this->tuple(Tuple::TASK_DELETE);
        $image = new \stdClass();
        $tuple->state->setRelation('image', $image);

        $this->relation()->queue($pool, $tuple);

        Assert::null($pool->offsetGet($image));
        Assert::same($tuple->state->getRelationStatus('image'), RelationInterface::STATUS_PREPARE);
    }

    private function tuple(int $task): Tuple
    {
        $node = new Node(Node::MANAGED, ['id' => 1], 'user');
        $tuple = new Tuple($task, new \stdClass(), true, Tuple::STATUS_PREPARING);
        $tuple->node = $node;
        $tuple->state = $node->createState();
        return $tuple;
    }

    private function relation(): MorphedHasOne
    {
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn(m::mock(SchemaInterface::class));
        $orm->allows('getService')->with(MapperProviderInterface::class)->andReturn(m::mock(MapperProviderInterface::class));
        $orm->allows('getService')->with(EntityProviderInterface::class)->andReturn(m::mock(EntityProviderInterface::class));

        return new MorphedHasOne($orm, 'user', 'image', 'image', [
            Relation::INNER_KEY => 'id',
            Relation::OUTER_KEY => 'parent_id',
            Relation::MORPH_KEY => 'parent_type',
            Relation::CASCADE => true,
            Relation::NULLABLE => false,
        ]);
    }
}
