<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Classless;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ClasslessHasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Classless\ClasslessHasOneCyclicTest
{
    public const DRIVER = 'sqlserver';
}
