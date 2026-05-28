<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\IntEnumDiscriminatorTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class IntEnumDiscriminatorTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
