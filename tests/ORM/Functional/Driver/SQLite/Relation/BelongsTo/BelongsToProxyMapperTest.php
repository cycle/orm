<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToProxyMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class BelongsToProxyMapperTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
