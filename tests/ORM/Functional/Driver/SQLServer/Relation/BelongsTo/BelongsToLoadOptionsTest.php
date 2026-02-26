<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToLoadOptionsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BelongsToLoadOptionsTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
