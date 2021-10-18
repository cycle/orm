<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessInverseRelationTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class ClasslessInverseRelationTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
