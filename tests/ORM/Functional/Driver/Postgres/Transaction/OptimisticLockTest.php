<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Transaction;

/**
 * @group driver
 * @group driver-postgres
 */
class OptimisticLockTest extends \Cycle\ORM\Tests\Functional\Transaction\OptimisticLockTest
{
    public const DRIVER = 'postgres';
}
