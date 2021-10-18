<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\BelongsTo;

/**
 * @group driver
 * @group driver-sqlite
 */
class BelongsToRelationTest extends \Cycle\ORM\Tests\Functional\Relation\BelongsTo\BelongsToRelationTest
{
    public const DRIVER = 'sqlite';
}
