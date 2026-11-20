<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Reference;

use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation\ActiveRelationInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class PromiseTest
{
    public function delegatesToOriginReference(): void
    {
        $origin = new Reference('post', ['user_id' => 1]);
        $promise = new Promise(m::mock(ActiveRelationInterface::class), $origin);

        Assert::same($promise->getRole(), 'post');
        Assert::same($promise->getScope(), ['user_id' => 1]);
        Assert::false($promise->hasValue());

        $promise->setValue(['post']);

        Assert::true($origin->hasValue());
        Assert::same($promise->getValue(), ['post']);
    }

    public function fetchResolvesOriginOnce(): void
    {
        $origin = new Reference('post', ['user_id' => 1]);
        $relation = m::mock(ActiveRelationInterface::class);
        $relation->expects('resolve')->once()->with($origin, true)->andReturnUsing(static function (Reference $ref): array {
            $ref->setValue(['loaded']);
            return ['loaded'];
        });
        $relation->allows('collect')->with(['loaded'])->andReturn(['collected']);
        $promise = new Promise($relation, $origin);

        Assert::same($promise->fetch(), ['collected']);
        Assert::same($promise->fetch(), ['collected']);
    }
}
