<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastEnumTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class TypecastEnumTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
