<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyScopeTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasManyScopeTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
