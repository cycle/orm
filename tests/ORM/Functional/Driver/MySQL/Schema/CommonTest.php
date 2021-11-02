<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\CommonTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class CommonTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
