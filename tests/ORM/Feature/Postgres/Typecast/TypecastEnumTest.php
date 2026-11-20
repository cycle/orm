<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Postgres\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Typecast\TypecastEnumTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 *
 * @requires PHP >= 8.1
 */
class TypecastEnumTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
