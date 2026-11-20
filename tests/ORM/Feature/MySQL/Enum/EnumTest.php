<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\MySQL\Enum;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Enum\EnumTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 *
 * @requires PHP >= 8.1
 */
class EnumTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
