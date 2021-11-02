<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\RefersTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RefersTo\RefersToProxyMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class RefersToProxyMapperTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
