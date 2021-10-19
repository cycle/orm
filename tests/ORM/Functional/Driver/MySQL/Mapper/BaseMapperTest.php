<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\BaseMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class BaseMapperTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
