<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Relation\ShadowBelongsTo;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ShadowBelongsTo::class)]
final class ShadowBelongsToTest
{
    public function isNotCascadeByDefault(): void
    {
        $relation = new ShadowBelongsTo('posts', 'user', [Relation::TARGET => 'post', Relation::SCHEMA => []]);

        Assert::false($relation->isCascade());
    }

    public function takesCascadeFromSchema(): void
    {
        $relation = new ShadowBelongsTo('posts', 'user', [
            Relation::TARGET => 'post',
            Relation::SCHEMA => [Relation::CASCADE => 1],
        ]);

        Assert::true($relation->isCascade());
    }

    public function nullableRelationOfWaitedEntityIsResolved(): void
    {
        $relation = new ShadowBelongsTo('posts', 'user', [
            Relation::TARGET => 'post',
            Relation::SCHEMA => [Relation::NULLABLE => true, Relation::OUTER_KEY => 'user_id'],
        ]);
        $tuple = new Tuple(Tuple::TASK_STORE, new \stdClass(), true, Tuple::STATUS_WAITED);
        $tuple->state = new State(Node::NEW, []);

        $relation->queue(new Pool(m::mock(ORMInterface::class)), $tuple);

        Assert::same($tuple->state->getRelationStatus('user.posts:post'), RelationInterface::STATUS_RESOLVED);
    }
}
