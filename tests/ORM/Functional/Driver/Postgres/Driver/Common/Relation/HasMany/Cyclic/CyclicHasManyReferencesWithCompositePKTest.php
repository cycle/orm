<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\HasMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic\CyclicHasManyReferencesWithCompositePKTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class CyclicHasManyReferencesWithCompositePKTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
