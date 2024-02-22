<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\JsonTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
final class JsonTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
