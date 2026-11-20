<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Options\LoadMethod;
use Testo\Assert;
use Testo\Test;

#[Test]
final class LoadMethodTest
{
    public function testSingleQueryValue(): void
    {
        Assert::same(LoadMethod::SingleQuery->value, Select::SINGLE_QUERY);
        Assert::same(LoadMethod::SingleQuery->value, JoinableLoader::INLOAD);
    }

    public function testOuterQueryValue(): void
    {
        Assert::same(LoadMethod::OuterQuery->value, Select::OUTER_QUERY);
        Assert::same(LoadMethod::OuterQuery->value, JoinableLoader::POSTLOAD);
    }

    public function testCaseCount(): void
    {
        Assert::count(LoadMethod::cases(), 2);
    }

    public function testFromValue(): void
    {
        Assert::same(LoadMethod::from(JoinableLoader::INLOAD), LoadMethod::SingleQuery);
        Assert::same(LoadMethod::from(JoinableLoader::POSTLOAD), LoadMethod::OuterQuery);
    }
}
