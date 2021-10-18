<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-postgres
 */
class CyclicHasManyReferencesWithCompositePKTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\Cyclic\CyclicHasManyReferencesWithCompositePKTest
{
    public const DRIVER = 'postgres';
}
