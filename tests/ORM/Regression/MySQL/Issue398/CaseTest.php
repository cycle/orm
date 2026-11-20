<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\MySQL\Issue398;

// phpcs:ignore
use Cycle\ORM\Tests\Regression\Issue398\CaseTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class CaseTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testSelectWithFullJoin(): void
    {
        $this->markTestSkipped('MySQL does not support FULL JOIN');
    }
}
