<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\StdMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class StdMapperTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
