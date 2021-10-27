<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessHasOneCyclicTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ClasslessHasOneCyclicTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
