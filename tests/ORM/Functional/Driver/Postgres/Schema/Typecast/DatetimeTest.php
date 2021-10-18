<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Schema\Typecast;

/**
 * @group driver
 * @group driver-postgres
 */
class DatetimeTest extends \Cycle\ORM\Tests\Functional\Schema\Typecast\DatetimeTest
{
    public const DRIVER = 'postgres';
}
