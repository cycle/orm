<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Inheritance\JTI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\CompositePKTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class CompositePKTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
