<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Transaction;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Transaction\OptimisticLockTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class OptimisticLockTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
