<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Transaction;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Transaction\RunnerTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class RunnerTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
