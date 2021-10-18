<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\PersistRepositoryTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class PersistRepositoryTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
