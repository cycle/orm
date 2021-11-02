<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\SoftDeletesTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class SoftDeletesTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
