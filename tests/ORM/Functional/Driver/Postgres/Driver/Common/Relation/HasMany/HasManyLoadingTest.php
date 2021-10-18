<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyLoadingTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class HasManyLoadingTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
