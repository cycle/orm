<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\SimpleTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class SimpleTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
