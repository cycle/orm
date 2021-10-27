<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToProxyMapperRenamedFieldsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BelongsToProxyMapperRenamedFieldsTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
