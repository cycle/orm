<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyScopeTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyScopeTest
{
    public const DRIVER = 'sqlserver';
}
