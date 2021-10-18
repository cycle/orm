<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyScopedTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyScopedTest
{
    public const DRIVER = 'sqlserver';
}
