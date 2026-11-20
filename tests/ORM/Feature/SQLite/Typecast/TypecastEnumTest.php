<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLite\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Typecast\TypecastEnumTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 *
 * @requires PHP >= 8.1
 */
class TypecastEnumTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
