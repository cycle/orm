<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\ORMTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ORMTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
