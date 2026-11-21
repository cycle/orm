<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Relation\ShadowHasMany;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ShadowHasManyTest
{
    public function describesTheHiddenSideOfTheRelation(): void
    {
        $relation = new ShadowHasMany('post.author:user', 'post', ['id'], ['author_id']);

        Assert::same($relation->getName(), 'post.author:user');
        Assert::same($relation->getTarget(), 'post');
        Assert::same($relation->getInnerKeys(), ['id']);
        Assert::true($relation->isCascade());
    }

    public function queuePushesKeysIntoRelatedEntities(): void
    {
        $relation = new ShadowHasMany('posts', 'post', ['id'], ['author_id']);
        $pool = new Pool(m::mock(ORMInterface::class));
        $post = new \stdClass();
        $postTuple = $pool->attach($post, Tuple::TASK_STORE, true, new Node(Node::NEW, [], 'post'), new State(Node::NEW, []));
        $tuple = new Tuple(Tuple::TASK_STORE, new \stdClass(), true, Tuple::STATUS_PROCESSED);
        $tuple->state = new State(Node::MANAGED, ['id' => 5]);
        $tuple->state->setRelation('posts', [$post]);

        $relation->queue($pool, $tuple);

        Assert::same($postTuple->state->getData()['author_id'], 5);
        Assert::same($postTuple->state->getRelationStatus('posts:post'), RelationInterface::STATUS_RESOLVED);
        Assert::same($tuple->state->getRelationStatus('posts'), RelationInterface::STATUS_RESOLVED);
    }
}
