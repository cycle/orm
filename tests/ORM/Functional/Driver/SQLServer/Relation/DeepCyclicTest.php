<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation;

/**
 * @group driver
 * @group driver-sqlserver
 */
class DeepCyclicTest extends \Cycle\ORM\Tests\Functional\Relation\DeepCyclicTest
{
    public const DRIVER = 'sqlserver';
}
