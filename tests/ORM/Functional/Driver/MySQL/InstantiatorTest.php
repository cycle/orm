<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\InstantiatorTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class InstantiatorTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
