<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Select;

/**
 * @group driver
 * @group driver-postgres
 */
class PaginateTest extends \Cycle\ORM\Tests\Functional\Select\PaginateTest
{
    public const DRIVER = 'postgres';
}
