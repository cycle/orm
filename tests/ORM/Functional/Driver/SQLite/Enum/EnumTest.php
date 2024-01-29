<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Enum;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Enum\EnumTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 *
 * @requires PHP >= 8.1
 */
class EnumTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
