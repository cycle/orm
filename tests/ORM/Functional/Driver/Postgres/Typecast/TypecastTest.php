<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class TypecastTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
