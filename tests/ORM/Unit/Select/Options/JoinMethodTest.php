<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Options\JoinMethod;
use Testo\Assert;
use Testo\Test;

#[Test]
final class JoinMethodTest
{
    public function testInnerJoinValue(): void
    {
        Assert::same(JoinMethod::InnerJoin->value, JoinableLoader::JOIN);
    }

    public function testLeftJoinValue(): void
    {
        Assert::same(JoinMethod::LeftJoin->value, JoinableLoader::LEFT_JOIN);
    }

    public function testCaseCount(): void
    {
        Assert::count(JoinMethod::cases(), 2);
    }

    public function testFromValue(): void
    {
        Assert::same(JoinMethod::from(JoinableLoader::JOIN), JoinMethod::InnerJoin);
        Assert::same(JoinMethod::from(JoinableLoader::LEFT_JOIN), JoinMethod::LeftJoin);
    }
}
