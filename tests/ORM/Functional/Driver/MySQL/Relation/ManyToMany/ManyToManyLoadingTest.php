<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyLoadingTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyLoadingTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
