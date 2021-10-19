<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\FactoryTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class FactoryTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
