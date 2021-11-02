<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\PersistRepositoryTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class PersistRepositoryTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
