<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\Morphed;

/**
 * @group driver
 * @group driver-sqlite
 */
class BelongsToMorphedRelationTest extends \Cycle\ORM\Tests\Functional\Relation\Morphed\BelongsToMorphedRelationTest
{
    public const DRIVER = 'sqlite';
}
