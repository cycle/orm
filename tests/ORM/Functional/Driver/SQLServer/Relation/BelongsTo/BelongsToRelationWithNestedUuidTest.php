<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToRelationWithNestedUuidTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BelongsToRelationWithNestedUuidTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
