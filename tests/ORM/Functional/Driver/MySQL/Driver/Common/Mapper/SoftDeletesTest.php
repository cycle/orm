<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\SoftDeletesTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class SoftDeletesTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
