<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany\Cyclic;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CyclicManyToManyTypedTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\Cyclic\CyclicManyToManyTypedTest
{
    public const DRIVER = 'sqlserver';
}
