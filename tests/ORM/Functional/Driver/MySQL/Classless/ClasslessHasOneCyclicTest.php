<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Classless;

/**
 * @group driver
 * @group driver-mysql
 */
class ClasslessHasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Classless\ClasslessHasOneCyclicTest
{
    public const DRIVER = 'mysql';
}
