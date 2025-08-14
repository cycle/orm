<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Integration\Issue482;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\AbstractTestCase as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class AbstractTestCase extends CommonClass
{
    public const DRIVER = 'postgres';
}
