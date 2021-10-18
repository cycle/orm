<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Transaction;

/**
 * @group driver
 * @group driver-sqlserver
 */
class OptimisticLockTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Transaction\OptimisticLockTest
{
    public const DRIVER = 'sqlserver';
}
