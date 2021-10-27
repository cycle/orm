<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\DatetimeTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class DatetimeTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
