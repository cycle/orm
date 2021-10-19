<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\CyclicManyToManyTypedTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class CyclicManyToManyTypedTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
