<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\NestedEagerTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class NestedEagerTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
