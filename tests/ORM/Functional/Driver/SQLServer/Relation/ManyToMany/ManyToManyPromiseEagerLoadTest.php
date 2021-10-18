<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyPromiseEagerLoadTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyPromiseEagerLoadTest
{
    public const DRIVER = 'sqlserver';
}
