<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToRelationTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class BelongsToRelationTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
