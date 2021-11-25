<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\TransactionTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class TransactionTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
