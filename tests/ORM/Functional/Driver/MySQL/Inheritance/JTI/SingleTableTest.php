<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Inheritance\JTI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\SingleTableTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class SingleTableTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
