<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\SQLite\Issue398;

// phpcs:ignore
use Cycle\ORM\Tests\Regression\Issue398\CaseTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class CaseTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    #[\Testo\Test]
    #[\Testo\Skip('SQLite does not support FULL JOIN')]
    public function testSelectWithFullJoin(): void
    {
    }

    #[\Testo\Test]
    #[\Testo\Skip('SQLite does not support RIGHT JOIN')]
    public function testSelectWithRightJoin(): void
    {
    }
}
