<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessHasOneCyclicTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-mysql
 */
class ClasslessHasOneCyclicTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
