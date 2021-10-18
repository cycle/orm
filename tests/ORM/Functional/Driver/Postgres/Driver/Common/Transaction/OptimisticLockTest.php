<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Transaction;

/**
 * @group driver
 * @group driver-postgres
 */
class OptimisticLockTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Transaction\OptimisticLockTest
{
    public const DRIVER = 'postgres';
}
