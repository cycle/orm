<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Transaction;

/**
 * @group driver
 * @group driver-sqlite
 */
class OptimisticLockTest extends \Cycle\ORM\Tests\Functional\Transaction\OptimisticLockTest
{
    public const DRIVER = 'sqlite';
}
