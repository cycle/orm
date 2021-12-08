<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Transaction;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Transaction\RunnerTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class RunnerTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
