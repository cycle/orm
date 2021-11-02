<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\RefersTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RefersTo\RefersToRelationRenamedFieldsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class RefersToRelationRenamedFieldsTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
