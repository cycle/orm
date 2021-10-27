<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyRelationTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
