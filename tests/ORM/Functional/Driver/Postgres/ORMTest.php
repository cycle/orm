<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\ORMTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class ORMTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
