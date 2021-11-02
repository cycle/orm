<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessInverseRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ClasslessInverseRelationTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
