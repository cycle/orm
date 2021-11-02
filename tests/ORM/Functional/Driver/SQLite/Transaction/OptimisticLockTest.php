<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Transaction;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Transaction\OptimisticLockTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class OptimisticLockTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
