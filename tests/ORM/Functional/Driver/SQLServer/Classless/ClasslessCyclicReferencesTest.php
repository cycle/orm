<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessCyclicReferencesTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ClasslessCyclicReferencesTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
