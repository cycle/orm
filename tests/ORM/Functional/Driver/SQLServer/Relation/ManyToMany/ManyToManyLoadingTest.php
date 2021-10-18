<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyLoadingTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyLoadingTest
{
    public const DRIVER = 'sqlserver';
}
