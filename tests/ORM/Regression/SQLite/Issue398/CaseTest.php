<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\SQLite\Issue398;

// phpcs:ignore
use Cycle\ORM\Tests\Regression\Issue398\CaseTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class CaseTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    public function testSelectWithFullJoin(): void
    {
        $this->markTestSkipped('SQLite does not support FULL JOIN');
    }

    public function testSelectWithRightJoin(): void
    {
        $this->markTestSkipped('SQLite does not support RIGHT JOIN');
    }
}
