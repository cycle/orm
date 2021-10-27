<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\DatetimeTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class DatetimeTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
