<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Schema\Column;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Column\UUIDTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class UUIDTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
