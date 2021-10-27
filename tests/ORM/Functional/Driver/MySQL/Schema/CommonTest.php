<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\CommonTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class CommonTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
