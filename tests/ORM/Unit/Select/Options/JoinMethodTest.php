<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Options\JoinMethod;
use PHPUnit\Framework\TestCase;

final class JoinMethodTest extends TestCase
{
    public function testInnerJoinValue(): void
    {
        $this->assertSame(JoinableLoader::JOIN, JoinMethod::InnerJoin->value);
    }

    public function testLeftJoinValue(): void
    {
        $this->assertSame(JoinableLoader::LEFT_JOIN, JoinMethod::LeftJoin->value);
    }

    public function testCaseCount(): void
    {
        $this->assertCount(2, JoinMethod::cases());
    }

    public function testFromValue(): void
    {
        $this->assertSame(JoinMethod::InnerJoin, JoinMethod::from(JoinableLoader::JOIN));
        $this->assertSame(JoinMethod::LeftJoin, JoinMethod::from(JoinableLoader::LEFT_JOIN));
    }
}
