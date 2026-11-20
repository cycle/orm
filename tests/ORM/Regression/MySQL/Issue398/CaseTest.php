<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\MySQL\Issue398;

// phpcs:ignore
use Cycle\ORM\Tests\Regression\Issue398\CaseTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class CaseTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    #[\Testo\Skip('MySQL does not support FULL JOIN')]
    public function testSelectWithFullJoin(): void
    {
    }
}
