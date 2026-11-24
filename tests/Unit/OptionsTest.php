<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\ORM\Options;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Options::class)]
final class OptionsTest
{
    public function withersKeepOriginalOptions(): void
    {
        $options = new Options();

        $ignore = $options->withIgnoreUninitializedRelations(true);
        $group = $options->withGroupByToDeduplicate(true);

        Assert::false($options->ignoreUninitializedRelations);
        Assert::false($options->groupByToDeduplicate);
        Assert::true($ignore->ignoreUninitializedRelations);
        Assert::true($group->groupByToDeduplicate);
    }
}
