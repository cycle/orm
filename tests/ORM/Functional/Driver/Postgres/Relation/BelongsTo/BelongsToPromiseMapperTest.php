<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\BelongsTo;

/**
 * @group driver
 * @group driver-postgres
 */
class BelongsToPromiseMapperTest extends \Cycle\ORM\Tests\Functional\Relation\BelongsTo\BelongsToPromiseMapperTest
{
    public const DRIVER = 'postgres';
}
