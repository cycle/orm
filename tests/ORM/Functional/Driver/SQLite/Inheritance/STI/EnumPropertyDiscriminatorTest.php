<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\EnumPropertyDiscriminatorTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class EnumPropertyDiscriminatorTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
