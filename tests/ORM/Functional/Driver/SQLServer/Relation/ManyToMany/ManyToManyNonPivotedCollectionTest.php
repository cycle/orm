<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyNonPivotedCollectionTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyNonPivotedCollectionTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
