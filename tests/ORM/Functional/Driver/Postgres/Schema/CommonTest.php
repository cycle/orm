<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\CommonTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class CommonTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
