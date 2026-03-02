<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyLoadOptionsTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class HasManyLoadOptionsTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
