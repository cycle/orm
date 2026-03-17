<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\SelectAggregateTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class SelectAggregateTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
