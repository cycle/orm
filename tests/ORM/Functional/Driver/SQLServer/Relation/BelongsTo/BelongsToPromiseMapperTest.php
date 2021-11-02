<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToPromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BelongsToPromiseMapperTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
