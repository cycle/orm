<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic\CyclicHasManyReferencesTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class CyclicHasManyReferencesTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
