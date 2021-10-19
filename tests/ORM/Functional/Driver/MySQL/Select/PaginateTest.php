<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\PaginateTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class PaginateTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
