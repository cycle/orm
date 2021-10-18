<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\RenamedPKTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class RenamedPKTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
