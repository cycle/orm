<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyScopedPivotTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyScopedPivotTest
{
    public const DRIVER = 'sqlite';
}
