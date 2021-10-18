<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\StdMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class StdMapperTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
