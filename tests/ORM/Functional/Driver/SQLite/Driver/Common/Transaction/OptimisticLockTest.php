<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Transaction;

/**
 * @group driver
 * @group driver-sqlite
 */
class OptimisticLockTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Transaction\OptimisticLockTest
{
    public const DRIVER = 'sqlite';
}
