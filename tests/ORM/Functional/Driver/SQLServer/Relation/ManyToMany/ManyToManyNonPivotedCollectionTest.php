<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyNonPivotedCollectionTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyNonPivotedCollectionTest
{
    public const DRIVER = 'sqlserver';
}
