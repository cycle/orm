<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic\CyclicHasManyReferencesWithCompositePKTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicHasManyReferencesWithCompositePKTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
