<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicHasManyReferencesTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\Cyclic\CyclicHasManyReferencesTest
{
    public const DRIVER = 'sqlite';
}
