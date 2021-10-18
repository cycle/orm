<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\BelongsTo;

/**
 * @group driver
 * @group driver-postgres
 */
class BelongsToProxyMapperTest extends \Cycle\ORM\Tests\Functional\Relation\BelongsTo\BelongsToProxyMapperTest
{
    public const DRIVER = 'postgres';
}
