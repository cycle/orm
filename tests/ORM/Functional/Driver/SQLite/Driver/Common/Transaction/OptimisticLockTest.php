<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Transaction;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Transaction\OptimisticLockTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class OptimisticLockTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
