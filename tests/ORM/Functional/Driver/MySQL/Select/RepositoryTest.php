<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\RepositoryTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class RepositoryTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
