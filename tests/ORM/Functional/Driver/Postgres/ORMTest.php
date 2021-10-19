<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\ORMTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class ORMTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
