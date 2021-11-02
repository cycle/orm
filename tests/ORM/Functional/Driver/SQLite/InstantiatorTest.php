<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\InstantiatorTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class InstantiatorTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
