<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\PaginateTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class PaginateTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
