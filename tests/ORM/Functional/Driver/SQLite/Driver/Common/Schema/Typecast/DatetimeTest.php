<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Schema\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Typecast\DatetimeTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class DatetimeTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
