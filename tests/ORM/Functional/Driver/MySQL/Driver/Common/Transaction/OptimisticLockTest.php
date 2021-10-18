<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Transaction;

/**
 * @group driver
 * @group driver-mysql
 */
class OptimisticLockTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Transaction\OptimisticLockTest
{
    public const DRIVER = 'mysql';
}
