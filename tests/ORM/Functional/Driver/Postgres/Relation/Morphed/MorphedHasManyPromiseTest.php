<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasManyPromiseTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class MorphedHasManyPromiseTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
