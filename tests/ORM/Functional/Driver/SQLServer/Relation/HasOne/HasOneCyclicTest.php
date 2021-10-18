<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasOne;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Relation\HasOne\HasOneCyclicTest
{
    public const DRIVER = 'sqlserver';
}
