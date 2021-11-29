<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\TransactionTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class TransactionTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
