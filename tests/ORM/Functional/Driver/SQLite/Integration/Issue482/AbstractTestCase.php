<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Integration\Issue482;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\AbstractTestCase as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class AbstractTestCase extends CommonClass
{
    public const DRIVER = 'sqlite';
}
