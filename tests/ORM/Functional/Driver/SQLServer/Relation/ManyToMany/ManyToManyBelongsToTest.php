<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyBelongsToTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyBelongsToTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
