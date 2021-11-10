<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastIteratorTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class TypecastIteratorTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
