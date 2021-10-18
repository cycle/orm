<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasManyScopeTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class MorphedHasManyScopeTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
