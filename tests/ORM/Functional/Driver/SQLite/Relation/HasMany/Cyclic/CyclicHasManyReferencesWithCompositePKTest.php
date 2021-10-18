<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicHasManyReferencesWithCompositePKTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\Cyclic\CyclicHasManyReferencesWithCompositePKTest
{
    public const DRIVER = 'sqlite';
}
