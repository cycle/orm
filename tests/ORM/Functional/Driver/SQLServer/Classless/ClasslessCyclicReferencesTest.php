<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Classless;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ClasslessCyclicReferencesTest extends \Cycle\ORM\Tests\Functional\Classless\ClasslessCyclicReferencesTest
{
    public const DRIVER = 'sqlserver';
}
