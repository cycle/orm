<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\BelongsToMorphedRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class BelongsToMorphedRelationTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
