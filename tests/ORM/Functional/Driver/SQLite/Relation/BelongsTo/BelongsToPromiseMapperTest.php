<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\BelongsTo;

/**
 * @group driver
 * @group driver-sqlite
 */
class BelongsToPromiseMapperTest extends \Cycle\ORM\Tests\Functional\Relation\BelongsTo\BelongsToPromiseMapperTest
{
    public const DRIVER = 'sqlite';
}
