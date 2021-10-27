<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\StdMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class StdMapperTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
