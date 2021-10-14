<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Driver\SQLServer\Transaction;

class OptimisticLockTest extends \Cycle\ORM\Tests\Transaction\OptimisticLockTest
{
    public const DRIVER = 'sqlserver';
}
