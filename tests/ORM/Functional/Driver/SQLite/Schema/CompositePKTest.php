<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\CompositePKTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class CompositePKTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
