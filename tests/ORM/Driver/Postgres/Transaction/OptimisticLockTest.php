<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Driver\Postgres\Transaction;

class OptimisticLockTest extends \Cycle\ORM\Tests\Transaction\OptimisticLockTest
{
    public const DRIVER = 'postgres';
}
