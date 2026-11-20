<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Postgres\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Typecast\JsonTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
final class JsonTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
