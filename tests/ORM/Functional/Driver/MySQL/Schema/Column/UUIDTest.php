<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Schema\Column;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Column\UUIDTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class UUIDTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
