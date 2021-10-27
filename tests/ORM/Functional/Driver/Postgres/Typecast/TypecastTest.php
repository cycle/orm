<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class TypecastTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
