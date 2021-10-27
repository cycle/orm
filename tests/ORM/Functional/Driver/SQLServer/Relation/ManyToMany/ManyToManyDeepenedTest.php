<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyDeepenedTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyDeepenedTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
