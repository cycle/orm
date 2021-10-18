<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\RenamedPKTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class RenamedPKTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
