<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\UUIDTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class UUIDTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
