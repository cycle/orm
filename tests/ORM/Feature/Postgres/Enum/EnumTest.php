<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Postgres\Enum;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Enum\EnumTest as CommonClass;

/**
 *
 * @requires PHP >= 8.1
 */
#[\Testo\Filter\Group('driver', 'driver-postgres')]
class EnumTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
