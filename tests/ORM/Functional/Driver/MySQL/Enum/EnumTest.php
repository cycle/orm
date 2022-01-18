<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Enum;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Enum\EnumTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class EnumTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
