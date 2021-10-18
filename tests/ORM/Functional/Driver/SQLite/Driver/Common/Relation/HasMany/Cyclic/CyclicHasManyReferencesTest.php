<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicHasManyReferencesTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic\CyclicHasManyReferencesTest
{
    public const DRIVER = 'sqlite';
}
