<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\EnumPropertyDiscriminatorTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class EnumPropertyDiscriminatorTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
