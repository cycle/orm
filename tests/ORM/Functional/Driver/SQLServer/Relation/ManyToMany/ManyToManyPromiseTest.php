<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyPromiseTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyPromiseTest
{
    public const DRIVER = 'sqlserver';
}
