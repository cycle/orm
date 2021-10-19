<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Schema\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Typecast\TypecastTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class TypecastTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
