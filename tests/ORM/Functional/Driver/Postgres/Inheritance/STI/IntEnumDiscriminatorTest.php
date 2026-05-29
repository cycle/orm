<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\IntEnumDiscriminatorTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class IntEnumDiscriminatorTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
