<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\CustomRepositoryTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class CustomRepositoryTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
