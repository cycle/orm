<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\EnumDiscriminatorTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class EnumDiscriminatorTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
