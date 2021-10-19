<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessCyclicReferencesTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ClasslessCyclicReferencesTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
