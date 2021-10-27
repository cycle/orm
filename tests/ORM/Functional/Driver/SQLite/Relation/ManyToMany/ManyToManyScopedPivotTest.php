<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyScopedPivotTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyScopedPivotTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
