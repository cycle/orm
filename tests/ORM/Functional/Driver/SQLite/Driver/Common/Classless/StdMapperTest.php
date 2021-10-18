<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\StdMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class StdMapperTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
