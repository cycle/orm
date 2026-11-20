<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Options\JoinMethod;
use PHPUnit\Framework\TestCase;

final class JoinMethodTest
{
    #[\Testo\Test]
    public function testInnerJoinValue(): void
    {
        \Testo\Assert::same(JoinMethod::InnerJoin->value, JoinableLoader::JOIN);
    }

    #[\Testo\Test]
    public function testLeftJoinValue(): void
    {
        \Testo\Assert::same(JoinMethod::LeftJoin->value, JoinableLoader::LEFT_JOIN);
    }

    #[\Testo\Test]
    public function testCaseCount(): void
    {
        \Testo\Assert::count(JoinMethod::cases(), 2);
    }

    #[\Testo\Test]
    public function testFromValue(): void
    {
        \Testo\Assert::same(JoinMethod::from(JoinableLoader::JOIN), JoinMethod::InnerJoin);
        \Testo\Assert::same(JoinMethod::from(JoinableLoader::LEFT_JOIN), JoinMethod::LeftJoin);
    }
}
