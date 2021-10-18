<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\ManyToMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\CyclicManyToManyTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class CyclicManyToManyTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
