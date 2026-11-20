<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Options\LoadMethod;
use PHPUnit\Framework\TestCase;

final class LoadMethodTest
{
    #[\Testo\Test]
    public function testSingleQueryValue(): void
    {
        \Testo\Assert::same(LoadMethod::SingleQuery->value, Select::SINGLE_QUERY);
        \Testo\Assert::same(LoadMethod::SingleQuery->value, JoinableLoader::INLOAD);
    }

    #[\Testo\Test]
    public function testOuterQueryValue(): void
    {
        \Testo\Assert::same(LoadMethod::OuterQuery->value, Select::OUTER_QUERY);
        \Testo\Assert::same(LoadMethod::OuterQuery->value, JoinableLoader::POSTLOAD);
    }

    #[\Testo\Test]
    public function testCaseCount(): void
    {
        \Testo\Assert::count(LoadMethod::cases(), 2);
    }

    #[\Testo\Test]
    public function testFromValue(): void
    {
        \Testo\Assert::same(LoadMethod::from(JoinableLoader::INLOAD), LoadMethod::SingleQuery);
        \Testo\Assert::same(LoadMethod::from(JoinableLoader::POSTLOAD), LoadMethod::OuterQuery);
    }
}
