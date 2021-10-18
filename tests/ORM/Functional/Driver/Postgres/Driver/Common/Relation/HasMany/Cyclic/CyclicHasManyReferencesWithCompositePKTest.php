<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-postgres
 */
class CyclicHasManyReferencesWithCompositePKTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic\CyclicHasManyReferencesWithCompositePKTest
{
    public const DRIVER = 'postgres';
}
