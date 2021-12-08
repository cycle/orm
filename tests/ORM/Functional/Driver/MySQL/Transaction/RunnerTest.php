<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Transaction;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Transaction\RunnerTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class RunnerTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
