<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\CommonTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class CommonTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
