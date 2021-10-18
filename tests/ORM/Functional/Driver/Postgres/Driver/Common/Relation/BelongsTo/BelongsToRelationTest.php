<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class BelongsToRelationTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
