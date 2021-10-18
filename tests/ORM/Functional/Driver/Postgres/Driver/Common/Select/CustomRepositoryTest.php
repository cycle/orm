<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\CustomRepositoryTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class CustomRepositoryTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
