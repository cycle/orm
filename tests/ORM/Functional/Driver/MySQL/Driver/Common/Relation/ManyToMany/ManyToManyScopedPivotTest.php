<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyScopedPivotTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyScopedPivotTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
