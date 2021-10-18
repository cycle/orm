<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-postgres
 */
class CyclicHasManyReferencesTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\Cyclic\CyclicHasManyReferencesTest
{
    public const DRIVER = 'postgres';
}
