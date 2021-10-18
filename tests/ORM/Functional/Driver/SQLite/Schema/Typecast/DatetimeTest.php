<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Schema\Typecast;

/**
 * @group driver
 * @group driver-sqlite
 */
class DatetimeTest extends \Cycle\ORM\Tests\Functional\Schema\Typecast\DatetimeTest
{
    public const DRIVER = 'sqlite';
}
