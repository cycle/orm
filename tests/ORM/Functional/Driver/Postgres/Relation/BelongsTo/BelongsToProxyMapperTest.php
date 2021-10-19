<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToProxyMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class BelongsToProxyMapperTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
