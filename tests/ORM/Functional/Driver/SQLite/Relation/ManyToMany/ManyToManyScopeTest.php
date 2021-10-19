<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyScopeTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyScopeTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
