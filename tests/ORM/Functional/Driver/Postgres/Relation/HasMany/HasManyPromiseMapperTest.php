<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyPromiseMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class HasManyPromiseMapperTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
