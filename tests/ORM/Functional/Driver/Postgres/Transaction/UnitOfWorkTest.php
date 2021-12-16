<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Transaction;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Transaction\UnitOfWorkTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class UnitOfWorkTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
