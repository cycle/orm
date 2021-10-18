<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Transaction;

/**
 * @group driver
 * @group driver-mysql
 */
class OptimisticLockTest extends \Cycle\ORM\Tests\Functional\Transaction\OptimisticLockTest
{
    public const DRIVER = 'mysql';
}
